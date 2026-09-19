# WhatsApp document-share message and link UX correction

Date: 2026-09-13

## Reported issue

- The PDF URL in the prefilled WhatsApp message was not providing a reliable clickable experience.
- The long signed URL appeared inline with the instruction text and made the message difficult for the recipient to scan.
- Vehicle Service invoice recipients also needed the invoice total, paid amount, and balance due in the message.

## Root cause

The application correctly passed the original URL through `wa.me`; regression coverage now confirms that query characters such as `?`, `&`, and `=` survive encoding and decode back to the exact message.

The current local environment generates links from:

```text
APP_URL=http://127.0.0.1:8000
```

`127.0.0.1` always refers to the recipient device itself. It is not an externally reachable AutoERP address, and WhatsApp clients do not provide a reliable public-link experience for this local HTTP URL. This is an environment/addressing issue rather than dropped link characters.

## Implementation

- Added an Invoice-owned WhatsApp message builder.
- Invoice messages now include authoritative persisted Invoice values:
  - invoice total from `grand_total`;
  - paid amount from `paid_total`;
  - balance due from `balance_due`.
- Amounts use the invoice's immutable currency symbol/code snapshot and a readable two-decimal display.
- Invoice and Purchase Order messages now place the unmodified absolute PDF URL on a separate line under a bold WhatsApp label.
- The link is not wrapped in Markdown `[label](url)` or HTML. WhatsApp Click to Chat does not support hiding a URL behind custom anchor text in an ordinary prefilled message.
- Existing recipient selection, verification warning, signed-link expiry, lifecycle checks, auditing, and PDF download behavior are unchanged.

## Resulting invoice message

```text
Hello Customer,

*Invoice SERVICE-2026-000001*

*Invoice total:* LKR 12,500.00
*Paid amount:* LKR 5,000.00
*Balance due:* LKR 7,500.00

*View or download PDF:*
https://erp.example.com/shared/invoices/...
```

WhatsApp renders the `*...*` labels in bold and receives the PDF URL as plain absolute text on its own line.

## Required URL configuration

For real customer/supplier sharing, production must use an externally reachable HTTPS application URL:

```dotenv
APP_URL=https://erp.example.com
```

After changing deployment environment configuration, rebuild Laravel's configuration cache:

```sh
php artisan config:cache
```

For local testing from a phone, a free HTTPS tunnel may be used temporarily and its public URL set as `APP_URL`. A temporary tunnel URL is not a production document address and may change when the tunnel restarts.

## Verification

- WhatsApp focused suite: **6 tests passed, 19 assertions**.
- Invoice module suite: **25 tests passed, 143 assertions**.
- Purchase Order API suite: **35 tests passed, 323 assertions**.
- TypeScript typecheck passed.
- Production frontend build passed with **664 modules transformed**.
- PHP formatter passed for all changed PHP files.

## Deployment

No database migration is required. Deploy the backend/config changes, configure a public HTTPS `APP_URL`, run `php artisan config:cache`, and deploy the rebuilt frontend assets.
