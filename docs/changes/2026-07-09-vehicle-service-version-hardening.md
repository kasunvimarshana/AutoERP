# Vehicle Service version hardening

## Context

Vehicle Service public write requests require `expected_version`, but the shared backend assertion accepted a missing version when services were invoked internally or directly. That left a concurrency guard gap outside the normal HTTP request path.

## Change

- Made the shared Vehicle Service expected-version assertion fail closed when `expected_version` is missing.
- Passed the locked job row version explicitly through trusted internal inspection, invoice, and payment status transitions.
- Preserved the existing public API request shape and did not modify unrelated Vehicle Service lifecycle semantics.

## Verification

- Reviewed Vehicle Service controller/request paths to confirm public mutating routes already pass `expected_version`.
- Reviewed internal status transitions that previously relied on nullable version handling and made them explicit.
- No documents or tests were treated as source of truth for the fix.

## Follow-up

The broader Vehicle Service lifecycle split remains open: operational, billing, and payment state should be separated in a dedicated follow-up change.
