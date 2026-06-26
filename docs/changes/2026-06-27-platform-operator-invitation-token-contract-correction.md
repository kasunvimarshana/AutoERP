# Platform operator invitation token contract correction

Date: 2026-06-27

## Context

The platform operator registration page reported that valid invitation links were incomplete. The backend invitation codec issues a 72-character URL-safe Base64 token. URL-safe Base64 can contain letters, digits, `-`, and `_`. The frontend accepted only letters and digits, so valid generated tokens containing `-` or `_` were rejected before the invitation inspection API was called.

## Decision

Keep the existing secure, opaque, one-time URL-safe Base64 token format. Correct the frontend validator to implement the backend token contract exactly. Do not weaken backend token validation, add alternate legacy token formats, expose tokens in the UI, or bypass invitation inspection.

## Changes

- Updated the platform operator invitation-page token validator to accept the complete URL-safe Base64 alphabet: `A-Z`, `a-z`, `0-9`, `-`, and `_`.
- Updated the positive invitation-page regression fixture to contain both `-` and `_`, preventing the previous all-alphanumeric fixture from hiding contract drift.
- Left tenant administrator invitation validation unchanged because that flow intentionally uses a different 64-character hexadecimal token contract.

## Verification

- TypeScript parser diagnostics: zero for both changed files.
- Regression token length: 72 characters.
- Regression token includes both URL-safe Base64 characters `-` and `_`.
- Valid-token validator accepts the realistic token.
- Invalid-token validator continues to reject malformed tokens.
- Backend invitation codec, routes, persistence, migrations, and security policy were not changed.

## Runtime verification

Run the frontend invitation test and click a newly issued invitation email in the local environment. Existing invitations remain valid because the backend token format did not change.
