<?php
namespace Opencart\Catalog\Model\Extension\Cigalpay\Payment;
/**
 * Class Cigalpay
 *
 * @package Opencart\Catalog\Model\Extension\Cigalpay\Payment
 */
class Cigalpay extends \Opencart\System\Engine\Model {
	/** CigalPay prices orders in these currencies, up to this amount. */
	public const CURRENCIES = ['USD', 'EUR'];
	public const MAX_AMOUNT = 5000;

	/**
	 * Offered when it can take the order: credentials set, the customer's currency
	 * is USD or EUR, at most 5,000 in it, and no subscription in the cart.
	 *
	 * @param array<string, mixed> $address
	 *
	 * @return array<string, mixed>
	 */
	public function getMethods(array $address = []): array {
		$this->load->language('extension/cigalpay/payment/cigalpay');

		$currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));

		$status = $this->config->get('payment_cigalpay_account_id')
			&& $this->config->get('payment_cigalpay_ipn_secret')
			&& in_array($currency, self::CURRENCIES, true)
			&& !$this->cart->hasSubscription()
			// Hidden above CigalPay's limit, instead of failing after the customer picks it.
			&& $this->currency->convert($this->cart->getTotal(), (string)$this->config->get('config_currency'), $currency) <= self::MAX_AMOUNT;

		if ($status && $this->config->get('payment_cigalpay_geo_zone_id') && $this->config->get('config_checkout_payment_address') && $address) {
			$this->load->model('localisation/geo_zone');

			$status = (bool)$this->model_localisation_geo_zone->getGeoZone((int)$this->config->get('payment_cigalpay_geo_zone_id'), (int)$address['country_id'], (int)$address['zone_id']);
		}

		if (!$status) {
			return [];
		}

		return [
			'code'       => 'cigalpay',
			'name'       => $this->language->get('heading_title'),
			'option'     => [
				'cigalpay' => [
					'code' => 'cigalpay.cigalpay',
					'name' => $this->language->get('heading_title')
				]
			],
			'sort_order' => $this->config->get('payment_cigalpay_sort_order')
		];
	}

	/**
	 * CigalPay data of an order (checkouts created, statuses handled, payment, refunds).
	 *
	 * @return array<string, mixed>
	 */
	public function getRecord(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cigalpay_order` WHERE `order_id` = '" . (int)$order_id . "'");

		$row = $query->row;

		return [
			'checkout_ids' => json_decode($row['checkout_ids'] ?? '[]', true) ?: [],
			'link'         => (string)($row['link'] ?? ''),
			'seen'         => json_decode($row['seen'] ?? '[]', true) ?: [],
			'payment_id'   => (string)($row['payment_id'] ?? ''),
		];
	}

	/** @param array<string, mixed> $record */
	public function saveRecord(int $order_id, array $record): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "cigalpay_order` SET `order_id` = '" . (int)$order_id . "', `checkout_ids` = '" . $this->db->escape(json_encode(array_values($record['checkout_ids']))) . "', `link` = '" . $this->db->escape($record['link']) . "', `seen` = '" . $this->db->escape(json_encode(array_values($record['seen']))) . "', `payment_id` = '" . $this->db->escape($record['payment_id']) . "' ON DUPLICATE KEY UPDATE `checkout_ids` = VALUES(`checkout_ids`), `link` = VALUES(`link`), `seen` = VALUES(`seen`), `payment_id` = VALUES(`payment_id`)");
	}
}
