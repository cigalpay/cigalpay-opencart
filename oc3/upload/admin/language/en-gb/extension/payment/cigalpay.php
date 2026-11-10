<?php
// Heading
$_['heading_title']          = 'CigalPay';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified CigalPay!';
$_['text_edit']              = 'Edit CigalPay';
$_['text_cigalpay']          = '<a href="https://cigalpay.com" target="_blank" rel="noopener"><b>CigalPay</b></a>';
$_['text_intro']             = 'Accept Bitcoin, USDT, Ethereum, Solana and more. Customers pay on the CigalPay checkout; the order moves to the paid status once the payment is confirmed on the network. Your credentials are in the CigalPay dashboard → Settings → API credentials.';
$_['text_notify']            = 'Payment notifications are sent to this address automatically, nothing to set up:';
$_['text_currency_warning']  = 'CigalPay prices orders in USD or EUR. Your default currency is %s: CigalPay is offered only to customers shopping in USD or EUR.';
$_['text_all_zones']         = 'All Zones';
$_['text_payment']           = 'Payment';
$_['text_checkouts']         = 'Checkouts created';
$_['text_payment_page']      = 'Payment page';
$_['text_refunds']           = 'Refunds';
$_['text_refund']            = 'Refund in crypto';
$_['text_refund_help']       = 'The amount is converted into the coin the customer paid, at the payment\'s rate, and comes out of your CigalPay balance. The customer gets an email to enter their address; CigalPay sends the coin once the refund is reviewed.';
$_['text_no_api_key']        = 'Add your CigalPay API key in the extension settings to refund in crypto from here.';
$_['text_not_paid']          = 'Not paid yet.';
$_['text_refund_reason']     = 'Refund of order %s';
$_['text_refund_started']    = 'CigalPay: refund of %s started (%s). The customer got an email to enter their address: %s. CigalPay sends it once reviewed.';

// Entry
$_['entry_account_id']       = 'Account ID';
$_['entry_ipn_secret']       = 'Notification signing secret';
$_['entry_api_key']          = 'API key';
$_['entry_pending_status']   = 'Waiting for payment status';
$_['entry_order_status']     = 'Paid status';
$_['entry_review_status']    = 'Needs review status';
$_['entry_geo_zone']         = 'Geo Zone';
$_['entry_debug']            = 'Debug log';
$_['entry_status']           = 'Status';
$_['entry_sort_order']       = 'Sort Order';
$_['entry_amount']           = 'Amount (%s)';
$_['entry_reason']           = 'Reason';

// Help
$_['help_ipn_secret']        = 'Used to check that payment notifications really come from CigalPay.';
$_['help_api_key']           = 'Only needed to refund orders in crypto from the order page.';
$_['help_review_status']     = 'For payments that need a look before shipping: partly paid, or not matching the order total.';
$_['help_debug']             = 'Log every notification in System → Maintenance → Error Logs. Errors are always logged.';

// Button
$_['button_refund']          = 'Refund';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify CigalPay!';
$_['error_account_id']       = 'Enter your CigalPay Account ID.';
$_['error_ipn_secret']       = 'Enter your notification signing secret.';
$_['error_not_paid']         = 'This order has no CigalPay payment to refund.';
$_['error_api_key']          = 'Add your CigalPay API key in the extension settings to refund in crypto.';
$_['error_amount']           = 'Enter the amount to refund.';
$_['error_refused']          = 'CigalPay: %s';
