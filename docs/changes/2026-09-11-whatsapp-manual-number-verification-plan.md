# WhatsApp Manual Number Verification Plan

Date: 2026-09-11

## Change

Added `docs/whatsapp-manual-number-verification-plan.md`, an implementation-ready specification for a free, API-free WhatsApp ownership-confirmation workflow for customer and supplier numbers.

The plan defines:

- A short-lived reply-code flow opened through WhatsApp Click to Chat.
- Honest **Manually verified** status semantics and employee confirmation requirements.
- Exact-number binding and automatic re-verification requirements after number changes.
- Authorization, security, expiry, retry, concurrency, audit-history, and error-handling rules.
- Integration expectations for the future **Share via WhatsApp** action while preserving the existing PDF download action.
- A deferred path to an official API/webhook integration without changing the core verification domain rules.

## Reason

The project needs a no-subscription method to reduce document-sharing mistakes and confirm that customers or suppliers can reply from their recorded WhatsApp number, while clearly acknowledging that AutoERP will not automatically read WhatsApp replies in the free implementation.
