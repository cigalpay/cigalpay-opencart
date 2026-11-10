<?php
namespace Cigalpay;
/**
 * The CigalPay calls the store needs. Same requests and signature check as
 * the CigalPay PHP SDK (cigalpay/cigalpay-php-sdk).
 */
class Api {
	public const CHECKOUT_URL = 'https://coins-api.cigalpay.com';
	public const MERCHANT_API_URL = 'https://merchant-api.cigalpay.com';

	/** Payment statuses sent in notifications. CONFIRMED and FINISHED mean paid. */
	public const STATUS_WAITING = 'WAITING';
	public const STATUS_CONFIRMING = 'CONFIRMING';
	public const STATUS_CONFIRMED = 'CONFIRMED';
	public const STATUS_FINISHED = 'FINISHED';
	public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
	public const STATUS_EXPIRED = 'EXPIRED';
	public const STATUS_FAILED = 'FAILED';

	/**
	 * Creates a hosted checkout. Returns its reply (id, chargeLink, ...).
	 *
	 * @param array<string, mixed> $checkout accountId, amount, currency, extraId, description, lang, callback, ipn
	 *
	 * @throws \RuntimeException with CigalPay's reason
	 *
	 * @return array<string, mixed>
	 */
	public static function createCheckout(array $checkout): array {
		[$status, $data] = self::post(self::base('CIGALPAY_CHECKOUT_URL', self::CHECKOUT_URL) . '/api/pre-invoice', $checkout, []);

		if ($status < 200 || $status >= 300 || empty($data['id']) || empty($data['chargeLink'])) {
			throw new \RuntimeException(sprintf('HTTP %d: %s', $status, is_string($data['error'] ?? null) ? $data['error'] : 'unexpected reply'));
		}

		return $data;
	}

	/**
	 * Refunds a paid payment from the CigalPay balance. Returns the reply
	 * (refund, link: where the customer enters their address).
	 *
	 * @param array<string, mixed> $refund paymentId, checkoutAmount (in the order currency), customerEmail, reason
	 *
	 * @throws \RuntimeException with CigalPay's reason
	 *
	 * @return array<string, mixed>
	 */
	public static function createRefund(string $api_key, array $refund): array {
		[$status, $data] = self::post(self::base('CIGALPAY_MERCHANT_API_URL', self::MERCHANT_API_URL) . '/api/refunds', $refund, ['cigalpay-api-key: ' . $api_key]);

		if ($status < 200 || $status >= 300 || empty($data['refund']['id'])) {
			throw new \RuntimeException(is_string($data['error'] ?? null) ? $data['error'] : sprintf('unexpected reply (HTTP %d)', $status));
		}

		return $data;
	}

	/** Checks a notification's x-cigalpay-sig header against the raw body, as received. */
	public static function verifyNotification(string $raw_body, string $signature, string $secret): bool {
		if ($raw_body === '' || $signature === '' || $secret === '') {
			return false;
		}

		if (!class_exists('\Cigalpay\Webhook')) {
			require_once __DIR__ . '/webhook.php';
		}

		$signed = \Cigalpay\Webhook::canonical($raw_body);

		return $signed !== null && hash_equals(hash_hmac('sha512', $signed, $secret), $signature);
	}

	/** True when a notification's status means the order is paid. */
	public static function isPaid(string $status): bool {
		return $status === self::STATUS_CONFIRMED || $status === self::STATUS_FINISHED;
	}

	/** The API address; a constant in config.php can point it elsewhere (tests). */
	private static function base(string $constant, string $default): string {
		return rtrim(defined($constant) ? (string)constant($constant) : $default, '/');
	}

	/**
	 * @param array<string, mixed> $body
	 * @param string[]             $headers
	 *
	 * @return array{0: int, 1: array<string, mixed>}
	 */
	private static function post(string $url, array $body, array $headers): array {
		$curl = curl_init($url);

		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode($body),
			CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 20,
		]);

		$response = curl_exec($curl);
		$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);

		curl_close($curl);

		if ($response === false) {
			throw new \RuntimeException('CigalPay unreachable: ' . $error);
		}

		$data = json_decode((string)$response, true);

		return [$status, is_array($data) ? $data : []];
	}
}
