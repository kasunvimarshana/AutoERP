# WhatsApp document sharing

## Purpose

AutoERP provides a free, user-initiated WhatsApp sharing flow for customer invoices, including Vehicle Service job invoices, and supplier purchase orders. It keeps the existing PDF download behavior unchanged.

The application does not call the WhatsApp Business/Cloud API and does not send a message automatically. It opens the selected recipient conversation through `wa.me` with a prefilled message and a secure PDF link. The user reviews the chat and presses **Send** in WhatsApp.

## User interface

- Invoice Detail shows **Share via WhatsApp** immediately after **Download PDF** for posted, partially paid, or paid outbound customer invoices.
- Purchase Order Detail shows **Share via WhatsApp** immediately after **Download PDF** for approved or closed purchase orders.
- Vehicle Service bills use the linked Invoice Detail action, so Vehicle Service does not duplicate Invoice PDF or sharing logic.
- Draft, reversed, cancelled, or void invoices and draft, pending-approval, or cancelled purchase orders do not show a share action.
- Existing **Print** and **Download PDF** actions retain their previous behavior.

## Automatic recipient selection

For a customer invoice, the backend selects:

1. the active customer contact with a mobile number, preferring the primary contact; or
2. the customer master mobile number when no eligible contact mobile exists.

For a purchase order, the same rule is applied to the supplier and its contacts.

Recipient selection is performed by the backend from the document relationship. The frontend cannot submit an arbitrary customer, supplier, contact, or phone number.

## Phone-number handling

WhatsApp requires a digits-only international number. The shared Core service:

- accepts common spaces, brackets, dashes, `+`, and `00` international prefixes;
- converts a local number using the configured default country calling code;
- avoids adding the configured calling code twice; and
- rejects numbers outside the E.164 length range.

Configuration:

```dotenv
DOCUMENT_SHARE_DEFAULT_COUNTRY_CALLING_CODE=94
```

The default is Sri Lanka (`94`) and may be changed per deployment without changing application code.

## Signed PDF link lifetime

WhatsApp PDF links use temporary Laravel signatures. They expire after 30 days by default:

```dotenv
DOCUMENT_SHARE_LINK_TTL_MINUTES=43200
```

The lifetime is configurable, but must remain a positive number. A permanent public link is intentionally not provided because anyone receiving or forwarding that secret URL could access the financial document indefinitely.

The user can generate a fresh WhatsApp link at any time from AutoERP. The authenticated **Download PDF** action remains available without this 30-day sharing limit.

Shared-link access performs a fresh lifecycle check. An otherwise valid link returns `404` after its Invoice or Purchase Order becomes cancelled, reversed, void, or otherwise non-shareable. Laravel's `signed` middleware returns `403` for an expired or modified URL.

`APP_URL` must be the externally reachable HTTPS AutoERP URL in production. A localhost `APP_URL` creates a link that a recipient's device cannot reach.

## HTTP endpoints

Authenticated endpoints, protected by the existing document view permission and tenant/organization scope:

- `POST /api/v1/invoices/{invoice}/whatsapp-share`
- `POST /api/v1/purchase/orders/{order}/whatsapp-share`

The response contains the automatically resolved recipient, normalized number, prefilled `wa.me` URL, signed document URL, and expiry timestamp.

Signed public PDF endpoints:

- `GET /shared/invoices/{invoice}/pdf/{tenant}`
- `GET /shared/purchase-orders/{order}/pdf/{tenant}`

The tenant and organization-unit values are included in the signed URL and checked again while resolving the document.

## Auditing and privacy

Generating a share action records a workflow audit event:

- `invoice.whatsapp_share.opened`
- `purchase.order.whatsapp_share.opened`

The event means only that AutoERP opened the WhatsApp handoff. It does not claim that the user sent the message or that WhatsApp delivered/read it. Full phone numbers and document URLs are not stored in audit metadata.

## Cost and limitations

- No WhatsApp/Meta API token, approved template, webhook, queue, or per-message API charge is required.
- The browser cannot both attach the actual PDF file and preselect a WhatsApp recipient through the free `wa.me` flow. The prefilled message therefore contains the signed PDF link.
- The user must press **Send** in WhatsApp.
- Delivery and read status are unavailable without an official WhatsApp API integration.

## Verification coverage

- Local and international phone normalization and invalid-number rejection.
- Safe frontend navigation restricted to `https://wa.me`.
- Invoice and Purchase Order shareable lifecycle policies.
- Signed Invoice PDF expiry and cancellation behavior.
- End-to-end approved Purchase Order share creation, recipient normalization, signed PDF download, audit write, and cancellation blocking.
- Existing Invoice and Purchase Order PDF regression suites.
- Full frontend TypeScript validation.

