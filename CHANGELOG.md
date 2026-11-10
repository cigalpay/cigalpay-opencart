# Changelog

## 1.0.0
- First release, for OpenCart 4 (4.0.2.0 and later) and OpenCart 3 (3.0.x).
- Crypto payments on the CigalPay checkout, priced in USD or EUR, hidden above 5,000.
- Orders updated from signed payment notifications: only the store's own checkouts count, the amount is checked, each status is handled once; partial or mismatched payments go to a "needs review" status.
- CigalPay tab on the admin order page: payment details and refunds in crypto.
