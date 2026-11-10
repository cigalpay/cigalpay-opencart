# opencart.com listing - CigalPay

Form: opencart.com → Manage your Extensions → Add Extension. Use the editor's code view (`</>`) to paste the HTML.

## Extension Name

CigalPay - Crypto Payments (Bitcoin, USDT, Ethereum)

## Description (HTML)

```html
<p><strong>Accept crypto payments in your OpenCart store.</strong> Your prices stay in USD or EUR; your customers pay with Bitcoin, USDT, Ethereum, Solana and more on the CigalPay checkout.</p>
<ul>
  <li><strong>Prices in USD or EUR:</strong> the customer sees the exact amount to send in the coin they choose. The exchange rate is locked for 30 minutes.</li>
  <li><strong>Coins:</strong> Bitcoin, USDT (TRC-20), Ethereum, Solana, Litecoin, Dogecoin, Dash, Bitcoin Cash, Polygon and TRX, plus many other coins converted automatically.</li>
  <li><strong>Automatic order status:</strong> the order moves to your paid status (Processing by default) once the payment is confirmed on the network, and the customer is notified.</li>
  <li><strong>Secure:</strong> every payment notification is signed, and the extension checks it is for the checkout it created for that order, at the order's price. Partial or mismatched payments are flagged for review instead of being accepted.</li>
  <li><strong>Refund in crypto from the order page:</strong> a CigalPay tab in Sales → Orders. The customer gets an email to enter their address.</li>
  <li><strong>No webhook to set up:</strong> the notification address is sent with every payment.</li>
  <li>Free extension. CigalPay charges a fee per payment; see <a href="https://cigalpay.com/pricing" target="_blank">cigalpay.com/pricing</a>.</li>
</ul>
<p><strong>Requirements:</strong> OpenCart 4.0.2.0 or later, PHP with cURL, a free CigalPay account (<a href="https://cigalpay.com" target="_blank">cigalpay.com</a>). Customers shopping in USD or EUR see the option; orders up to 5,000 USD or EUR.</p>
<p>Source code: <a href="https://github.com/cigalpay/cigalpay-opencart" target="_blank">github.com/cigalpay/cigalpay-opencart</a> · Support: support@cigalpay.com</p>
```

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

## Documentation (HTML)

```html
<h3>Install</h3>
<ol>
  <li>In your OpenCart admin, open <strong>Extensions → Installer</strong>, click <strong>Upload</strong> and choose <code>cigalpay-opencart4.ocmod.zip</code>. Then click the green <strong>Install</strong> button next to CigalPay in the list below.</li>
  <li>Open <strong>Extensions → Extensions</strong>, choose <strong>Payments</strong>, and click the green <strong>Install</strong> button next to CigalPay.</li>
  <li>Click <strong>Edit</strong> (the pencil) next to CigalPay.</li>
</ol>
<h3>Set up</h3>
<ol>
  <li>Create a free account at <a href="https://cigalpay.com" target="_blank">cigalpay.com</a>.</li>
  <li>In the CigalPay dashboard, open <strong>Settings → API credentials</strong> and copy your <strong>Account ID</strong> and <strong>notification signing secret</strong>. To refund from OpenCart, also copy your <strong>API key</strong>.</li>
  <li>Paste them in the CigalPay settings in OpenCart, choose the order statuses (defaults: Pending while waiting, Processing once paid), turn <strong>Status</strong> on and click <strong>Save</strong>.</li>
</ol>
<p>Nothing else to set up: payment notifications are sent to your store automatically. Your store must be reachable from the internet (not localhost).</p>
<h3>Refunds</h3>
<p>Open the order in <strong>Sales → Orders</strong>, then the <strong>CigalPay</strong> tab. Enter the amount (in the order currency) and click <strong>Refund</strong>. The amount is converted into the coin the customer paid and comes out of your CigalPay balance; the customer gets an email to enter their address.</p>
<h3>Troubleshooting</h3>
<ul>
  <li><strong>CigalPay doesn't show at checkout:</strong> check that Status is on, the Account ID and signing secret are saved, the customer's currency is USD or EUR, and the order is at most 5,000.</li>
  <li><strong>Orders stay Pending after payment:</strong> check the notification signing secret. Turn on <strong>Debug log</strong> and look at <strong>System → Maintenance → Error Logs</strong> (lines starting with "CigalPay").</li>
</ul>
<p>Questions: support@cigalpay.com</p>
```

## Extension Downloads

| Download Name | File | Compatibility (tick) |
|---|---|---|
| CigalPay for OpenCart 4 | `build/cigalpay-opencart4.ocmod.zip` | 4.0.2.0, 4.0.2.1, 4.0.2.2, 4.0.2.3, 4.1.0.0, 4.1.0.1, 4.1.0.2, 4.1.0.3, 4.1.0.4 |
| CigalPay for OpenCart 3 (later) | `build/cigalpay-opencart3.ocmod.zip` | 3.0.x |

Do **not** turn on the Cloud Marketplace option (it is the old "upload/install.xml" format).

## Extension Options

- Publish: Yes
- Notify when new comments are made: Yes
- Send update email to purchasers: Yes (useful for later updates)
