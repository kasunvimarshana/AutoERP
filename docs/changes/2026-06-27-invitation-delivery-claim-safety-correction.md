# Invitation delivery claim safety correction

Date: 2026-06-27

## Context

Tenant registration and platform-operator invitation jobs claim a delivery attempt before handing email to the configured mail transport. The claim lease prevents another worker from processing the same attempt concurrently, while invitation and recipient state determine whether the email is still valid.

The tenant worker revalidated the claimed row before mail handoff, but did not require the claim lease to remain active or bind the related invitation query to the exact invitation captured by the claim. The platform worker claimed a row once and then sent without a final state check. A revocation, expiry, token removal, operator lifecycle change, or lost lease between claim and transport handoff could therefore allow a stale job to send. Platform attempts that became permanently unsendable could also remain in an open delivery state.

## Decisions

- Treat the delivery claim token, row version, lease, invitation state, token availability, and recipient lifecycle as one send-time authorization boundary.
- Revalidate immediately before the external mail side effect; do not rely only on the earlier transactional claim.
- Keep tenant and platform invitation ownership in their existing modules. Do not restore cross-module Eloquent relationships.
- Cancel open attempts when an invitation is expired, revoked, replaced, missing its delivery token, or no longer has an invited recipient.
- Preserve conditional post-send finalization and emit an operational warning when transport handoff succeeds after the database claim changes.
- Do not claim exactly-once email delivery. The implementation provides database-backed duplicate suppression and stale-claim rejection around an external transport that cannot participate in the database transaction.

## Changes

- Tenant registration delivery now requires an unexpired lease and the exact invitation ID during the final pre-send check.
- Platform-operator delivery now performs a control-plane send-time revalidation using the exact delivery ID, claim token, row version, active lease, pending invitation, available delivery token, and invited operator state.
- Platform delivery cancels queued, sending, and failed attempts when the invitation becomes permanently unsendable.
- Platform delivery uses named constants for lease defaults and stable failure codes/messages.
- Platform delivery conditionally finalizes a sent attempt and logs a warning when the mail transport accepted the message after claim ownership changed.
- Added source-level regression tests covering tenant and platform pre-send claim validation and terminal cancellation behavior.

## Verification

- Changed PHP files pass syntax validation.
- Affected Auth, User, and Auth feature-test PHP files pass syntax validation.
- Invitation delivery claim-safety regression tests pass through the available PHPUnit runtime classes.
- TypeScript semantic checking passes with zero diagnostics.
- ESLint passes across the complete frontend source with zero errors and zero warnings.
- Full frontend suite passes: 48 files, 164 tests.
- Production Vite build passes: 654 transformed modules.
- Database-backed queue, mail-transport, lease-expiry, and concurrent-worker tests remain deployment-environment gates because this PHP CLI lacks the required PHPUnit extensions and PDO drivers.
