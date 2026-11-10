# opencart.com listing - CigalPay

Form: opencart.com → Manage your Extensions → Add Extension. Paste the texts below in the editor's normal view, as plain text: opencart.com's firewall (Cloudflare) blocks the form when it contains HTML. Use the editor's buttons for bold and lists if wanted.

## Extension Name

CigalPay - Crypto Payments (Bitcoin, USDT, Ethereum)

## Description

Accept crypto payments in your OpenCart store. Your prices stay in USD or EUR; your customers pay with Bitcoin, USDT, Ethereum, Solana and more on the CigalPay checkout.

- Prices in USD or EUR: the customer sees the exact amount to send in the coin they choose. The exchange rate is locked for 30 minutes.
- Coins: Bitcoin, USDT (TRC-20), Ethereum, Solana, Litecoin, Dogecoin, Dash, Bitcoin Cash, Polygon and TRX, plus many other coins converted automatically.
- Automatic order status: the order moves to your paid status (Processing by default) once the payment is confirmed on the network, and the customer is notified.
- Secure: every payment notification is signed, and the extension checks it is for the checkout it created for that order, at the order's price. Partial or mismatched payments are flagged for review instead of being accepted.
- Refund in crypto from the order page: a CigalPay tab in Sales > Orders. The customer gets an email to enter their address.
- No webhook to set up: the notification address is sent with every payment.
- Free extension. CigalPay charges a fee per payment, see cigalpay.com/pricing

Requirements: OpenCart 4.0.2.0 or later, or OpenCart 3.0.x (PHP 7.1+), with cURL, a free CigalPay account at cigalpay.com. Customers shopping in USD or EUR see the option; orders up to 5,000 USD or EUR.

Source code: github.com/cigalpay/cigalpay-opencart
Support: support@cigalpay.com

## Category

Payment Gateways

## License

Free (leave License Period, Price and Cloud Price empty).

## Tags

```
crypto, cryptocurrency, bitcoin, btc, usdt, tether, ethereum, solana, crypto payments, payment gateway, crypto payment gateway, cigalpay
```

## Images

- Image (260×152): `image-260x152.png`
- Banner (710×380): `banner-710x380.png`
- Additional images, in this order: `screenshot-1-checkout.png`, `screenshot-2-payment-page.png`, `screenshot-3-order-refund.png`, `screenshot-4-settings.png`

## Documentation

INSTALL
1. In your OpenCart admin, open Extensions > Installer, click Upload and choose the CigalPay zip file for your OpenCart version (cigalpay-opencart4.ocmod.zip or cigalpay-opencart3.ocmod.zip). On OpenCart 4, then click the green Install button next to CigalPay in the list below.
2. Open Extensions > Extensions, choose Payments, and click the green Install button next to CigalPay.
3. Click Edit (the pencil) next to CigalPay.

SET UP
1. Create a free account at cigalpay.com.
2. In the CigalPay dashboard, open Settings > API credentials and copy your Account ID and notification signing secret. To refund from OpenCart, also copy your API key.
3. Paste them in the CigalPay settings in OpenCart, choose the order statuses (defaults: Pending while waiting, Processing once paid), turn Status on and click Save.

Nothing else to set up: payment notifications are sent to your store automatically. Your store must be reachable from the internet (not localhost).

REFUNDS
Open the order in Sales > Orders, then the CigalPay tab. Enter the amount (in the order currency) and click Refund. The amount is converted into the coin the customer paid and comes out of your CigalPay balance; the customer gets an email to enter their address.

TROUBLESHOOTING
- CigalPay doesn't show at checkout: check that Status is on, the Account ID and signing secret are saved, the customer's currency is USD or EUR, and the order is at most 5,000.
- Orders stay Pending after payment: check the notification signing secret. Turn on Debug log and look at System > Maintenance > Error Logs (lines starting with "CigalPay").

Questions: support@cigalpay.com

## Extension Downloads

| Download Name | File | Compatibility (tick) |
|---|---|---|
| CigalPay for OpenCart 4 | `build/cigalpay-opencart4.ocmod.zip` | 4.0.2.0, 4.0.2.1, 4.0.2.2, 4.0.2.3, 4.1.0.0, 4.1.0.1, 4.1.0.2, 4.1.0.3, 4.1.0.4 |
| CigalPay for OpenCart 3 | `build/cigalpay-opencart3.ocmod.zip` | 3.0.2.0, 3.0.3.x, 3.0.4.x, 3.0.5.0, 3.0.5.1 |

Do **not** turn on the Cloud Marketplace option (it is the old "upload/install.xml" format).

## Extension Options

- Publish: Yes
- Notify when new comments are made: Yes
- Send update email to purchasers: Yes (useful for later updates)

Both packages are also attached to every GitHub release (built by `.github/workflows/release.yml`). Tested on OpenCart 4.1.0.4 and 3.0.5.1.
