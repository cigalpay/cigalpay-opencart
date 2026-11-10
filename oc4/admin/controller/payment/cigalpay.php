<?php
namespace Opencart\Admin\Controller\Extension\Cigalpay\Payment;
use Opencart\Extension\Cigalpay\System\Library\Api;
/**
 * Class Cigalpay
 *
 * Settings, the CigalPay tab of the order page, and refunds in crypto.
 *
 * @package Opencart\Admin\Controller\Extension\Cigalpay\Payment
 */
class Cigalpay extends \Opencart\System\Engine\Controller {
	private const ROUTE = 'extension/cigalpay/payment/cigalpay';

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language(self::ROUTE);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=payment')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link(self::ROUTE, $token)],
		];

		$data['save'] = $this->url->link(self::ROUTE . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=payment');

		foreach (['account_id', 'ipn_secret', 'api_key', 'pending_status_id', 'order_status_id', 'review_status_id', 'geo_zone_id', 'debug', 'status', 'sort_order'] as $key) {
			$data['payment_cigalpay_' . $key] = $this->config->get('payment_cigalpay_' . $key);
		}

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['notify_url'] = HTTP_CATALOG . 'index.php?route=extension/cigalpay/payment/cigalpay.notify';
		$data['currency_warning'] = in_array($this->config->get('config_currency'), ['USD', 'EUR'], true) ? '' : sprintf($this->language->get('text_currency_warning'), $this->config->get('config_currency'));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view(self::ROUTE, $data));
	}

	/**
	 * @return void
	 */
	public function save(): void {
		$this->load->language(self::ROUTE);

		$json = [];

		if (!$this->user->hasPermission('modify', self::ROUTE)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!trim((string)($this->request->post['payment_cigalpay_account_id'] ?? ''))) {
			$json['error']['account-id'] = $this->language->get('error_account_id');
		}

		if (!trim((string)($this->request->post['payment_cigalpay_ipn_secret'] ?? ''))) {
			$json['error']['ipn-secret'] = $this->language->get('error_ipn_secret');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_cigalpay', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Extensions → Payments → Install: the CigalPay data table and default statuses.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cigalpay_order` (
			`order_id` INT(11) NOT NULL,
			`checkout_ids` TEXT NOT NULL,
			`link` VARCHAR(512) NOT NULL DEFAULT '',
			`seen` TEXT NOT NULL,
			`payment_id` VARCHAR(64) NOT NULL DEFAULT '',
			`refund_ids` TEXT NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->load->model('setting/setting');

		if (!$this->model_setting_setting->getSetting('payment_cigalpay')) {
			$this->model_setting_setting->editSetting('payment_cigalpay', [
				'payment_cigalpay_pending_status_id' => (int)$this->config->get('config_order_status_id') ?: 1,
				'payment_cigalpay_order_status_id'   => $this->statusId(['Processing'], 2),
				'payment_cigalpay_review_status_id'  => (int)$this->config->get('config_order_status_id') ?: 1,
				'payment_cigalpay_status'            => 0,
				'payment_cigalpay_sort_order'        => 1,
			]);
		}
	}

	/**
	 * Uninstall keeps the cigalpay_order table: it is the store's payment record.
	 *
	 * @return void
	 */
	public function uninstall(): void {
	}

	/**
	 * The "CigalPay" tab of the admin order page.
	 *
	 * @return string
	 */
	public function order(): string {
		$this->load->language(self::ROUTE);

		$order_id = (int)($this->request->get['order_id'] ?? 0);
		$record = $this->record($order_id);

		if (!$record) {
			return '';
		}

		$this->load->model('sale/order');
		$order_info = $this->model_sale_order->getOrder($order_id);

		$data['order_id'] = $order_id;
		$data['user_token'] = $this->session->data['user_token'];
		$data['payment_id'] = $record['payment_id'];
		$data['checkout_ids'] = implode(', ', $record['checkout_ids']);
		$data['link'] = $record['link'];
		$data['refund_ids'] = implode(', ', $record['refund_ids']);
		$data['currency_code'] = $order_info['currency_code'] ?? '';
		$data['can_refund'] = $record['payment_id'] !== '' && trim((string)$this->config->get('payment_cigalpay_api_key')) !== '' && $this->user->hasPermission('modify', self::ROUTE);
		$data['no_api_key'] = $record['payment_id'] !== '' && trim((string)$this->config->get('payment_cigalpay_api_key')) === '';
		// url->link() encodes & as &amp; (for HTML); this one is used in JavaScript.
		$data['refund'] = str_replace('&amp;', '&', $this->url->link(self::ROUTE . '.refund', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id));

		return $this->load->view('extension/cigalpay/payment/cigalpay_order', $data);
	}

	/**
	 * Refunds in the coin the customer paid, from the CigalPay balance. The customer
	 * gets an email with a link to enter their address; CigalPay sends the coin
	 * once the refund is reviewed.
	 *
	 * @return void
	 */
	public function refund(): void {
		$this->load->language(self::ROUTE);

		$json = [];
		$order_id = (int)($this->request->get['order_id'] ?? 0);
		$amount = round((float)($this->request->post['amount'] ?? 0), 2);
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
			$body = [
				'paymentId'      => $record['payment_id'],
				'checkoutAmount' => $amount,
				'reason'         => mb_substr((string)($this->request->post['reason'] ?? ''), 0, 200) ?: sprintf($this->language->get('text_refund_reason'), $order_id),
			];

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
			$this->db->query("UPDATE `" . DB_PREFIX . "cigalpay_order` SET `refund_ids` = '" . $this->db->escape(json_encode($record['refund_ids'])) . "' WHERE `order_id` = '" . $order_id . "'");

			$coin = trim(($res['refund']['amount'] ?? '') . ' ' . str_replace('_', ' ', (string)($res['refund']['currency'] ?? '')));
			$comment = sprintf($this->language->get('text_refund_started'), $this->currency->format($amount, (string)$order_info['currency_code'], 1), $coin, (string)($res['link'] ?? ''));

			// A note in the order history, without changing the order status or emailing the customer (CigalPay emails them).
			$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . $order_id . "', `order_status_id` = '" . (int)$order_info['order_status_id'] . "', `notify` = '0', `comment` = '" . $this->db->escape($comment) . "', `date_added` = NOW()");

			$json['success'] = $comment;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function record(int $order_id): array {
		if (!$order_id) {
			return [];
		}

		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'cigalpay_order') . "'");

		if (!$query->num_rows) {
			return [];
		}

		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cigalpay_order` WHERE `order_id` = '" . $order_id . "'")->row;

		if (!$row) {
			return [];
		}

		return [
			'checkout_ids' => json_decode($row['checkout_ids'] ?: '[]', true) ?: [],
			'link'         => (string)$row['link'],
			'payment_id'   => (string)$row['payment_id'],
			'refund_ids'   => json_decode($row['refund_ids'] ?: '[]', true) ?: [],
		];
	}

	/**
	 * An order status id by its English name, with a fallback.
	 *
	 * @param string[] $names
	 */
	private function statusId(array $names, int $fallback): int {
		$this->load->model('localisation/order_status');

		foreach ($this->model_localisation_order_status->getOrderStatuses() as $status) {
			if (in_array($status['name'], $names, true)) {
				return (int)$status['order_status_id'];
			}
		}

		return $fallback;
	}
}
