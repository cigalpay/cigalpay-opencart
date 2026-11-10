<?php
namespace Opencart\Catalog\Controller\Extension\Cigalpay\Payment;
use Opencart\Extension\Cigalpay\System\Library\Api;
/**
 * Class Cigalpay
 *
 * Sends the customer to the CigalPay hosted checkout and updates the order
 * from CigalPay's signed payment notification.
 *
 * @package Opencart\Catalog\Controller\Extension\Cigalpay\Payment
 */
class Cigalpay extends \Opencart\System\Engine\Controller {
	private const LANGS = ['en', 'fr', 'es', 'de'];

	/**
	 * The payment block at checkout.
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('extension/cigalpay/payment/cigalpay');

		$data['language'] = $this->config->get('config_language');

		return $this->load->view('extension/cigalpay/payment/cigalpay', $data);
	}

	/**
	 * "Pay in crypto": creates the CigalPay checkout and sends the customer to it.
	 *
	 * @return void
	 */
	public function confirm(): void {
		$this->load->language('extension/cigalpay/payment/cigalpay');
		$this->load->model('checkout/order');
		$this->load->model('extension/cigalpay/payment/cigalpay');

		$json = [];
		$order_info = [];

		if (isset($this->session->data['order_id'])) {
			$order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);
		}

		if (!$order_info) {
			$json['error'] = $this->language->get('error_order');
		} elseif (!isset($this->session->data['payment_method']['code']) || $this->session->data['payment_method']['code'] != 'cigalpay.cigalpay') {
			$json['error'] = $this->language->get('error_payment_method');
		}

		if (!$json) {
			$currency = (string)$order_info['currency_code'];
			$amount = round((float)$this->currency->format((float)$order_info['total'], $currency, (float)$order_info['currency_value'], false), 2);

			if (!in_array($currency, \Opencart\Catalog\Model\Extension\Cigalpay\Payment\Cigalpay::CURRENCIES, true) || $amount <= 0 || $amount > \Opencart\Catalog\Model\Extension\Cigalpay\Payment\Cigalpay::MAX_AMOUNT) {
				$json['error'] = $this->language->get('error_unavailable');
			}
		}

		if (!$json) {
			$order_id = (int)$order_info['order_id'];

			try {
				$checkout = Api::createCheckout([
					'accountId'   => trim((string)$this->config->get('payment_cigalpay_account_id')),
					'amount'      => $amount,
					'currency'    => $currency,
					'extraId'     => (string)$order_id,
					'description' => mb_substr(sprintf($this->language->get('text_order'), $order_id, html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8')), 0, 200),
					'lang'        => $this->lang(),
					'callback'    => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true),
					'ipn'         => $this->notifyUrl(),
				]);
			} catch (\RuntimeException $e) {
				$this->log->write('CigalPay: order ' . $order_id . ': checkout not created: ' . $e->getMessage());

				$json['error'] = $this->language->get('error_checkout');
			}
		}

		if (!$json) {
			// Every checkout made for this order is accepted: a customer can come back and pay an earlier one.
			$record = $this->model_extension_cigalpay_payment_cigalpay->getRecord($order_id);
			$record['checkout_ids'][] = (string)$checkout['id'];
			$record['checkout_ids'] = array_values(array_unique($record['checkout_ids']));
			$record['link'] = (string)$checkout['chargeLink'];
			$this->model_extension_cigalpay_payment_cigalpay->saveRecord($order_id, $record);

			// The order leaves "missing" for the pending status: it shows in the admin, the cart is kept until the customer pays.
			$this->model_checkout_order->addHistory($order_id, (int)$this->config->get('payment_cigalpay_pending_status_id'), sprintf($this->language->get('text_waiting'), $checkout['chargeLink']));

			$json['redirect'] = (string)$checkout['chargeLink'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * CigalPay's payment notification (POST index.php?route=extension/cigalpay/payment/cigalpay.notify).
	 * Trusted only when the signature checks out and the payment is for a
	 * checkout this store created for that order, at that order's price.
	 *
	 * @return void
	 */
	public function notify(): void {
		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->status(405, 'Method Not Allowed');

			return;
		}

		$raw = (string)file_get_contents('php://input');
		$signature = (string)($this->request->server['HTTP_X_CIGALPAY_SIG'] ?? '');

		if (!Api::verifyNotification($raw, $signature, trim((string)$this->config->get('payment_cigalpay_ipn_secret')))) {
			$this->log->write('CigalPay: notification rejected: bad or missing signature. Check the notification signing secret in Extensions → Payments → CigalPay.');
			$this->status(401, 'Bad or missing signature');

			return;
		}

		$notification = json_decode($raw, true);

		if (!is_array($notification)) {
			$this->status(400, 'Bad Request');

			return;
		}

		$this->apply($notification);
		$this->status(200, 'OK');
	}

	/** OpenCart sends headers only with a body: set the status code directly. */
	private function status(int $code, string $text): void {
		http_response_code($code);
		$this->response->addHeader('Content-Type: text/plain');
		$this->response->setOutput($text);
	}

	/**
	 * @param array<string, mixed> $p a verified notification
	 */
	private function apply(array $p): void {
		$this->load->language('extension/cigalpay/payment/cigalpay');
		$this->load->model('checkout/order');
		$this->load->model('extension/cigalpay/payment/cigalpay');

		$payment_id = (string)($p['id'] ?? '');
		$status = (string)($p['status'] ?? '');
		$order_id = isset($p['extraId']) && ctype_digit((string)$p['extraId']) ? (int)$p['extraId'] : 0;
		$order_info = $order_id ? $this->model_checkout_order->getOrder($order_id) : [];

		$this->debug(sprintf('notification: payment %s, order %s, status %s', $payment_id, $p['extraId'] ?? '-', $status));

		if (!$order_info || ($order_info['payment_method']['code'] ?? '') !== 'cigalpay.cigalpay') {
			$this->debug('ignored: no CigalPay order with this reference');

			return;
		}

		$record = $this->model_extension_cigalpay_payment_cigalpay->getRecord($order_id);
		$checkout_id = (string)($p['checkoutId'] ?? '');

		if ($checkout_id === '' || !in_array($checkout_id, $record['checkout_ids'], true)) {
			// Anyone can create a checkout with this store's account and an order number; only the checkouts this store made count.
			$this->log->write(sprintf('CigalPay: order %d: ignored payment %s, it is not for a checkout this store created for the order.', $order_id, $payment_id));

			return;
		}

		// Each payment status is handled once (notifications can repeat).
		$key = $payment_id . ':' . $status;

		if (in_array($key, $record['seen'], true)) {
			return;
		}

		$record['seen'][] = $key;
		$record['seen'] = array_slice($record['seen'], -50);
		$this->model_extension_cigalpay_payment_cigalpay->saveRecord($order_id, $record);

		$coin = $this->formatCoin($p['amount'] ?? '', $p);
		$current = (int)$order_info['order_status_id'];
		$paid_status = (int)$this->config->get('payment_cigalpay_order_status_id');
		$review_status = (int)$this->config->get('payment_cigalpay_review_status_id');

		if (Api::isPaid($status)) {
			if ($record['payment_id'] !== '') {
				if ($record['payment_id'] !== $payment_id) {
					$this->model_checkout_order->addHistory($order_id, $current, sprintf($this->language->get('text_second_payment'), $coin, $payment_id));
				}

				return;
			}

			$order_amount = round((float)$this->currency->format((float)$order_info['total'], (string)$order_info['currency_code'], (float)$order_info['currency_value'], false), 2);
			$paid_amount = isset($p['checkoutAmount']) ? (float)$p['checkoutAmount'] : -1;
			$paid_currency = strtoupper((string)($p['checkoutCurrency'] ?? ''));

			if ($paid_currency !== $order_info['currency_code'] || abs($paid_amount - $order_amount) > 0.01) {
				$this->model_checkout_order->addHistory($order_id, $review_status, sprintf($this->language->get('text_mismatch'), $this->currency->format($paid_amount, $paid_currency, 1), $this->currency->format($order_amount, (string)$order_info['currency_code'], 1)));

				return;
			}

			$comment = sprintf($this->language->get('text_paid'), $coin, $payment_id);

			if (!empty($p['swapFromCurrency'])) {
				$comment .= ' ' . sprintf($this->language->get('text_swapped'), (string)($p['swapFromAmount'] ?? ''), (string)$p['swapFromCurrency']);
			}

			$record['payment_id'] = $payment_id;
			$this->model_extension_cigalpay_payment_cigalpay->saveRecord($order_id, $record);
			// Notifies the customer, as for any paid order.
			$this->model_checkout_order->addHistory($order_id, $paid_status, $comment, true);

			return;
		}

		if ($record['payment_id'] !== '') {
			return;
		}

		switch ($status) {
			case Api::STATUS_WAITING:
				$this->model_checkout_order->addHistory($order_id, $current, sprintf($this->language->get('text_chose'), $coin));
				break;
			case Api::STATUS_CONFIRMING:
				$this->model_checkout_order->addHistory($order_id, $current, sprintf($this->language->get('text_confirming'), $coin));
				break;
			case Api::STATUS_PARTIALLY_PAID:
				$this->model_checkout_order->addHistory($order_id, $review_status, sprintf($this->language->get('text_partial'), $this->formatCoin($p['amountPaid'] ?? 0, $p), $coin));
				break;
			case Api::STATUS_EXPIRED:
			case Api::STATUS_FAILED:
				$this->model_checkout_order->addHistory($order_id, $current, sprintf($this->language->get('text_expired'), $coin));
				break;
		}
	}

	private function notifyUrl(): string {
		return str_replace('&amp;', '&', $this->url->link('extension/cigalpay/payment/cigalpay.notify', '', true));
	}

	private function lang(): string {
		$lang = strtolower(substr((string)$this->config->get('config_language'), 0, 2));

		return in_array($lang, self::LANGS, true) ? $lang : 'en';
	}

	/**
	 * @param mixed                $amount
	 * @param array<string, mixed> $p
	 */
	private function formatCoin($amount, array $p): string {
		$coin = (string)($p['currency'] ?? '');
		$coin = $coin === 'USDT_TRX' ? 'USDT (TRC-20)' : str_replace('_', ' ', $coin);

		return trim($amount . ' ' . $coin);
	}

	private function debug(string $message): void {
		if ($this->config->get('payment_cigalpay_debug')) {
			$this->log->write('CigalPay: ' . $message);
		}
	}
}
