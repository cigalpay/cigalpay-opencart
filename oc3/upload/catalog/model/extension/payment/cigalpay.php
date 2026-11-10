<?php
/**
 * CigalPay: when it is offered at checkout, and the CigalPay data of an order.
 */
class ModelExtensionPaymentCigalpay extends Model {
	/** CigalPay prices orders in these currencies, up to this amount. */
	const CURRENCIES = array('USD', 'EUR');
	const MAX_AMOUNT = 5000;

	/**
	 * Offered when it can take the order: credentials set, the customer's currency
	 * is USD or EUR, at most 5,000 in it, and the geo zone matches.
	 */
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cigalpay');

		$currency = isset($this->session->data['currency']) ? (string)$this->session->data['currency'] : (string)$this->config->get('config_currency');

		$status = $this->config->get('payment_cigalpay_account_id')
			&& $this->config->get('payment_cigalpay_ipn_secret')
			&& in_array($currency, self::CURRENCIES, true)
			// Hidden above CigalPay's limit, instead of failing after the customer picks it.
			&& $this->currency->convert((float)$total, (string)$this->config->get('config_currency'), $currency) <= self::MAX_AMOUNT;

		if ($status && $this->config->get('payment_cigalpay_geo_zone_id')) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_cigalpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

			$status = (bool)$query->num_rows;
		}

		if (!$status) {
			return array();
		}

		return array(
			'code'       => 'cigalpay',
			'title'      => $this->language->get('heading_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_cigalpay_sort_order')
		);
	}

	public function getRecord($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cigalpay_order` WHERE `order_id` = '" . (int)$order_id . "'");

		$row = $query->row;

		return array(
			'checkout_ids' => !empty($row['checkout_ids']) ? (json_decode($row['checkout_ids'], true) ?: array()) : array(),
			'link'         => isset($row['link']) ? (string)$row['link'] : '',
			'seen'         => !empty($row['seen']) ? (json_decode($row['seen'], true) ?: array()) : array(),
			'payment_id'   => isset($row['payment_id']) ? (string)$row['payment_id'] : '',
		);
	}

	public function saveRecord($order_id, array $record) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "cigalpay_order` SET `order_id` = '" . (int)$order_id . "', `checkout_ids` = '" . $this->db->escape(json_encode(array_values($record['checkout_ids']))) . "', `link` = '" . $this->db->escape($record['link']) . "', `seen` = '" . $this->db->escape(json_encode(array_values($record['seen']))) . "', `payment_id` = '" . $this->db->escape($record['payment_id']) . "' ON DUPLICATE KEY UPDATE `checkout_ids` = VALUES(`checkout_ids`), `link` = VALUES(`link`), `seen` = VALUES(`seen`), `payment_id` = VALUES(`payment_id`)");
	}
}
