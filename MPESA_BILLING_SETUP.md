# M-PESA Subscription Billing

Run `database/migration_billing_v1.sql` once against the existing SaaS database before deploying the PHP changes.

For local testing, use the ignored file `.env` at the project root. Apache is listening on port 80, so start a separate tunnel for Kalmoy:

```text
ngrok http 80
```

Do not reuse the ALVORA tunnel unless it is explicitly configured to forward to this Kalmoy document root. Replace `REPLACE_WITH_KALMOY_NGROK_HOST` in `.env` with the hostname generated for the Kalmoy tunnel.

Set these server-only environment variables in `.env`:

```text
MPESA_ENV=production
MPESA_CONSUMER_KEY=REPLACE_WITH_PRIVATE_CONSUMER_KEY
MPESA_CONSUMER_SECRET=REPLACE_WITH_PRIVATE_CONSUMER_SECRET
MPESA_PASSKEY=REPLACE_WITH_PRIVATE_PASSKEY
MPESA_SHORTCODE=3537545
MPESA_PARTY_B=3598582
MPESA_TRANSACTION_TYPE=CustomerBuyGoodsOnline
MPESA_ACCOUNT_REFERENCE=KALMOY
MPESA_TRANSACTION_DESC=KALMOY POS Subscription
MPESA_CALLBACK_URL=https://REPLACE_WITH_KALMOY_NGROK_HOST/BestPosKalmoy/api/mpesa/callback.php
```

These are the only Daraja credentials/configuration values you need to replace. Keep them out of Git, HTML, JavaScript, and browser responses.

The callback must be publicly reachable over HTTPS. The application never activates a subscription when STK Push is merely accepted; activation occurs only after a successful callback with a matching amount and receipt.

For a host with cron support, run every five minutes:

```text
*/5 * * * * /usr/bin/php /path/to/htdocs/cron/process_billing.php
```

The job marks abandoned pending payments as `timeout` and persists expired subscription status. Daraja callbacks remain the source of truth for successful payments.
