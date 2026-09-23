# Free WhatsApp document sharing

Date: 2026-09-11

## Request

Implemented the approved free workflow for sharing customer bills and purchase orders through WhatsApp. Existing PDF download actions had to remain unchanged, the related recipient had to be selected automatically, and the WhatsApp action had to appear beside PDF download.

## Implementation

- Added **Share via WhatsApp** beside **Download PDF** on eligible Invoice and Purchase Order detail screens.
- Reused Invoice Detail for Vehicle Service job bills so Vehicle Service does not own duplicate PDF or WhatsApp logic.
- Added backend-owned recipient resolution from active Customer/Supplier contacts, preferring the primary mobile and falling back to the master mobile.
- Added configurable international phone normalization and digits-only `wa.me` URL generation.
- Added authenticated, tenant- and organization-scoped share-link endpoints protected by the existing document view permission.
- Added signed public Invoice and Purchase Order PDF routes with lifecycle checks.
- Kept existing Print and Download PDF flows unchanged.
- Added workflow audit events for opening the WhatsApp handoff without recording full phone numbers or claiming send/delivery/read status.
- Added no database migration or transactional-data change.

## Link lifetime decision

A non-expiring public financial-document URL was evaluated but rejected as unsafe because the URL could be forwarded and remain usable indefinitely. The implementation follows the agreed fallback plan:

- WhatsApp PDF links expire after 30 days by default;
- the lifetime is configurable through `DOCUMENT_SHARE_LINK_TTL_MINUTES`;
- expired or modified links are rejected by Laravel signature validation;
- cancelled, reversed, void, or otherwise non-shareable documents are rejected even before the signature expires; and
- an authorized AutoERP user can generate a fresh link at any time, while the normal authenticated PDF download remains available.

## Configuration

- `DOCUMENT_SHARE_DEFAULT_COUNTRY_CALLING_CODE=94`
- `DOCUMENT_SHARE_LINK_TTL_MINUTES=43200`

Production must configure an externally reachable HTTPS `APP_URL` so recipients can open the PDF link from their devices.

## Documentation

The implementation, security model, endpoints, limitations, and operational configuration are documented in `docs/whatsapp-document-sharing.md`.

## Verification

- WhatsApp link unit tests: 3 passed.
- Document lifecycle policy tests: 2 passed.
- Invoice print/share tests: 4 passed, including expiry and cancellation blocking.
- Purchase Order API regression suite: 35 passed, including an end-to-end WhatsApp share and signed PDF test.
- Frontend safe-navigation tests: 2 passed.
- TypeScript validation passed.
- Route registration and `git diff --check` passed.
