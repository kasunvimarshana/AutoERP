# Manual WhatsApp Number Verification Implementation

Date: 2026-09-11

## Summary

Implemented the API-free manual WhatsApp ownership verification flow defined in `docs/whatsapp-manual-number-verification-plan.md` for customers and suppliers.

An employee starts a verification from the customer or supplier detail page. AutoERP opens a Click-to-Chat message containing a short-lived code. After the customer or supplier replies with that code, the employee checks that the reply came from the same WhatsApp number, enters the code, confirms the ownership check, and marks the number as manually verified.

AutoERP does not read WhatsApp messages and no paid WhatsApp API is used.

## Backend

- Added a shared manual verification workflow in Core for phone normalization, code generation, hashing, expiry, attempt limits, idempotent challenge creation, and atomic confirmation.
- Added separate Customer- and Supplier-owned verification models, services, controllers, routes, and migrations.
- Verification codes are stored only as hashes.
- Existing verified records are immutable history. A changed contact or master mobile produces `number_changed` until the new number is verified.
- Verification state exposes the verifier's human-readable name, not a raw user ID.
- Challenge and confirmation endpoints use existing tenant authentication, module permissions, validation, and configurable rate limiting.
- Added environment-backed configuration for code TTL, maximum attempts, and rate limiting.

## Frontend

- Added a reusable WhatsApp verification panel to customer and supplier detail pages.
- The panel displays recipient, normalized number, current status, verification time, and verifier.
- Completing verification requires both a valid code and an explicit employee checkbox confirming the reply came from the displayed number.
- Invoice and purchase-order WhatsApp share responses now include the selected recipient's verification status.
- Sharing to a number that is not currently verified shows an explicit confirmation warning before WhatsApp opens. Sharing remains possible because verification is a manual assurance control, not an automated WhatsApp delivery dependency.

## Verification

- `php artisan test tests/Feature/ManualWhatsAppVerificationTest.php`: 2 tests passed, 16 assertions.
- Document share policy and focused purchase-order share API tests: 3 tests passed, 23 assertions.
- `npm run typecheck`: passed.
- `npm run build`: passed.
- ESLint on all changed WhatsApp verification and sharing frontend files: passed.

