<?php
require_once DIR_SYSTEM . 'library/cigalpay/api.php';

use Cigalpay\Api;

/**
 * CigalPay (OpenCart 3): settings, the CigalPay tab of the order page, and refunds in crypto.
 */
class ControllerExtensionPaymentCigalpay extends Controller {
	const ROUTE = 'extension/payment/cigalpay';

	private $error = array();

	/** Settings page. */
	public function index() {
		$this->load->language(self::ROUTE);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_cigalpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_account_id'] = isset($this->error['account_id']) ? $this->error['account_id'] : '';
		$data['error_ipn_secret'] = isset($this->error['ipn_secret']) ? $this->error['ipn_secret'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=payment', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link(self::ROUTE, $token, true)),
		);

		$data['action'] = $this->url->link(self::ROUTE, $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=payment', true);

		foreach (array('account_id', 'ipn_secret', 'api_key', 'pending_status_id', 'order_status_id', 'review_status_id', 'geo_zone_id', 'debug', 'status', 'sort_order') as $key) {
			$name = 'payment_cigalpay_' . $key;
			$data[$name] = isset($this->request->post[$name]) ? $this->request->post[$name] : $this->config->get($name);
		}

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['notify_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/cigalpay/notify';
		$data['currency_warning'] = in_array($this->config->get('config_currency'), array('USD', 'EUR'), true) ? '' : sprintf($this->language->get('text_currency_warning'), $this->config->get('config_currency'));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view(self::ROUTE, $data));
	}

	/** Extensions → Extensions → Payments → Install: the CigalPay data table and default statuses. */
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cigalpay_order` (
			`order_id` INT(11) NOT NULL,
			`checkout_ids` TEXT NOT NULL,
			`link` VARCHAR(512) NOT NULL DEFAULT '',
			`seen` TEXT NOT NULL,
			`payment_id` VARCHAR(64) NOT NULL DEFAULT '',
			`refund_ids` TEXT NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		$this->load->model('setting/setting');

		if (!$this->model_setting_setting->getSetting('payment_cigalpay')) {
			$pending = (int)$this->config->get('config_order_status_id') ?: 1;

			$this->model_setting_setting->editSetting('payment_cigalpay', array(
				'payment_cigalpay_pending_status_id' => $pending,
				'payment_cigalpay_order_status_id'   => $this->statusId(array('Processing'), 2),
				'payment_cigalpay_review_status_id'  => $pending,
				'payment_cigalpay_status'            => 0,
				'payment_cigalpay_sort_order'        => 1,
			));
		}
	}

	/** Uninstall keeps the cigalpay_order table: it is the store's payment record. */
	public function uninstall() {
	}

	/** The "CigalPay" tab of the admin order page. */
	public function order() {
		$this->load->language(self::ROUTE);

		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		$record = $this->record($order_id);

		if (!$record) {
			return '';
		}

		$this->load->model('sale/order');
		$order_info = $this->model_sale_order->getOrder($order_id);

		$has_key = trim((string)$this->config->get('payment_cigalpay_api_key')) !== '';

		$data['order_id'] = $order_id;
		$data['user_token'] = $this->session->data['user_token'];
		$data['payment_id'] = $record['payment_id'];
		$data['checkout_ids'] = implode(', ', $record['checkout_ids']);
		$data['link'] = $record['link'];
		$data['refund_ids'] = implode(', ', $record['refund_ids']);
		$data['currency_code'] = isset($order_info['currency_code']) ? $order_info['currency_code'] : '';
		$data['can_refund'] = $record['payment_id'] !== '' && $has_key && $this->user->hasPermission('modify', self::ROUTE);
		$data['no_api_key'] = $record['payment_id'] !== '' && !$has_key;
		// url->link() encodes & as &amp; (for HTML); this one is used in JavaScript.
		$data['refund'] = str_replace('&amp;', '&', $this->url->link(self::ROUTE . '/refund', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true));

		return $this->load->view('extension/payment/cigalpay_order', $data);
	}

	/**
	 * Refunds in the coin the customer paid, from the CigalPay balance. The customer
	 * gets an email with a link to enter their address; CigalPay sends the coin
	 * once the refund is reviewed.
	 */
	public function refund() {
		$this->load->language(self::ROUTE);

		$json = array();
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		$amount = round((float)(isset($this->request->post['amount']) ? $this->request->post['amount'] : 0), 2);
		$record = $this->record($order_id);

		$this->load->model('sale/order');
		$order_info = $this->model_sale_order->getOrder($order_id);

		if (!$this->user->hasPermission('modify', self::ROUTE)) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (!$record || $record['payment_id'] === '' || !$order_info) {
			$json['error'] = $this->language->get('error_not_paid');
		} elseif (trim((string)$this->config->get('payment_cigalpay_api_key')) === '') {
			$json['error'] = $this->language->get('error_api_key');
		} elseif ($amount <= 0) {
			$json['error'] = $this->language->get('error_amount');
		}

		if (!$json) {
			$reason = isset($this->request->post['reason']) ? (string)$this->request->post['reason'] : '';

			$body = array(
				'paymentId'      => $record['payment_id'],
				'checkoutAmount' => $amount,
				'reason'         => mb_substr($reason, 0, 200) ?: sprintf($this->language->get('text_refund_reason'), $order_id),
			);

			if (filter_var($order_info['email'], FILTER_VALIDATE_EMAIL)) {
				$body['customerEmail'] = $order_info['email'];
			}

			try {
				$res = Api::createRefund(trim((string)$this->config->get('payment_cigalpay_api_key')), $body);
			} catch (\RuntimeException $e) {
				$this->log->write(sprintf('CigalPay: order %d: refund of %s refused: %s', $order_id, $amount, $e->getMessage()));
				$json['error'] = sprintf($this->language->get('error_refused'), $e->getMessage());
			}
		}

		if (!$json) {
			$record['refund_ids'][] = (string)$res['refund']['id'];
			$this->db->query("UPDATE `" . DB_PREFIX . "cigalpay_order` SET `refund_ids` = '" . $this->db->escape(json_encode($record['refund_ids'])) . "' WHERE `order_id` = '" . (int)$order_id . "'");

			$coin = trim((isset($res['refund']['amount']) ? $res['refund']['amount'] : '') . ' ' . str_replace('_', ' ', isset($res['refund']['currency']) ? (string)$res['refund']['currency'] : ''));
			$comment = sprintf($this->language->get('text_refund_started'), $this->currency->format($amount, (string)$order_info['currency_code'], 1), $coin, isset($res['link']) ? (string)$res['link'] : '');

			// A note in the order history, without changing the order status or emailing the customer (CigalPay emails them).
			$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . (int)$order_id . "', `order_status_id` = '" . (int)$order_info['order_status_id'] . "', `notify` = '0', `comment` = '" . $this->db->escape($comment) . "', `date_added` = NOW()");

			$json['success'] = $comment;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', self::ROUTE)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!trim((string)(isset($this->request->post['payment_cigalpay_account_id']) ? $this->request->post['payment_cigalpay_account_id'] : ''))) {
			$this->error['account_id'] = $this->language->get('error_account_id');
		}

		if (!trim((string)(isset($this->request->post['payment_cigalpay_ipn_secret']) ? $this->request->post['payment_cigalpay_ipn_secret'] : ''))) {
			$this->error['ipn_secret'] = $this->language->get('error_ipn_secret');
		}

		return !$this->error;
	}

	private function record($order_id) {
		if (!$order_id) {
			return array();
		}

		if (!$this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'cigalpay_order') . "'")->num_rows) {
			return array();
		}

		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cigalpay_order` WHERE `order_id` = '" . (int)$order_id . "'")->row;

		if (!$row) {
			return array();
		}

		return array(
			'checkout_ids' => json_decode($row['checkout_ids'] ?: '[]', true) ?: array(),
			'link'         => (string)$row['link'],
			'payment_id'   => (string)$row['payment_id'],
			'refund_ids'   => json_decode($row['refund_ids'] ?: '[]', true) ?: array(),
		);
	}

	/** An order status id by its English name, with a fallback. */
	private function statusId(array $names, $fallback) {
		$this->load->model('localisation/order_status');

		foreach ($this->model_localisation_order_status->getOrderStatuses() as $status) {
			if (in_array($status['name'], $names, true)) {
				return (int)$status['order_status_id'];
			}
		}

		return $fallback;
	}
}
