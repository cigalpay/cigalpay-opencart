# CigalPay for OpenCart

Accept crypto payments (Bitcoin, USDT, Ethereum, Solana and more) in your OpenCart store. Prices stay in USD or EUR;
customers pick the coin on the [CigalPay](https://cigalpay.com) checkout, and the order moves to your paid status once the
payment is confirmed on the network.

- Signed payment notifications, checked against the checkout the extension created for the order and its price.
- Partial or mismatched payments are flagged for review instead of being accepted.
- A CigalPay tab on the admin order page: payment details and refunds in crypto (needs the API key).
- Offered to customers shopping in USD or EUR, up to 5,000 per order.

## Download

From the [latest release](https://github.com/cigalpay/cigalpay-opencart/releases/latest):

| OpenCart | Package |
|---|---|
| 4.0.2.0 and later | `cigalpay-opencart4.ocmod.zip` |
| 3.0.x | `cigalpay-opencart3.ocmod.zip` |

## Install

1. **Extensions → Installer**: upload the package for your OpenCart version.
   OpenCart 4: then click the green Install button next to CigalPay in the list.
2. **Extensions → Extensions → Payments**: click the green Install button next to CigalPay, then Edit.
3. Paste your Account ID and notification signing secret from the CigalPay dashboard (Settings → API credentials),
   and your API key to refund from OpenCart. Turn Status on and save.

Payment notifications are sent to your store automatically: nothing else to set up. Your store must be reachable from
the internet.

## Repository

| Folder | What |
|---|---|
| `oc4/` | OpenCart 4 extension (zip root: `install.json`, `admin/`, `catalog/`, `system/`) |
| `oc3/` | OpenCart 3 extension (zip root: `upload/`), PHP 7.1+ |
| `marketplace/` | opencart.com listing: texts (`listing.md`), images and screenshots |

## Release

Bump the version in `oc4/install.json`, add a `## X.Y.Z` entry to `CHANGELOG.md`, then push a tag `vX.Y.Z`.
GitHub Actions checks the PHP syntax and attaches both packages to a GitHub release.

## License

[MIT](LICENSE)
