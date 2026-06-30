# Platform operator invitation digest foundation

Date: 2026-06-30

## Problem

Platform operator invitation lookup used an HMAC derived from `APP_KEY` as the database token identity. That mixed two responsibilities: application encryption and invitation lookup.

When web, queue, CLI, cached configuration, or a later deployment used a different application key, the same invitation token produced a different lookup hash. Registration then returned that the invitation was invalid or unavailable.

The previous resend correction preserved live invitation tokens. That was correct for current tokens, but it also preserved legacy key-bound hashes, allowing a broken invitation to remain broken after every resend.

## Correct foundation

Platform invitation tokens contain 54 random bytes and are URL-safe encoded. Lookup now uses a domain-separated SHA-256 digest that is deterministic across application-key changes.

`APP_KEY` remains responsible for encrypting the temporary delivery copy of the token, but it no longer defines the durable database lookup identity.

## Transition behavior

- New invitations store the stable current digest.
- Lookup accepts the current digest and the legacy application-key-derived digest during transition.
- A current, decryptable invitation is preserved when resent.
- A legacy, unreadable, missing, or digest-mismatched invitation is revoked and replaced with a new stable invitation during resend.
- An already delivered current-format link remains inspectable after an application-key rotation because inspection and acceptance do not require the encrypted delivery copy.
- A worker that cannot decrypt or validate the delivery copy clears that copy and cancels open delivery attempts without revoking the pending invitation. An already delivered stable link therefore remains valid; the next resend replaces the unavailable delivery copy.
- Token lookup misses write a sanitized correlation-aware operational log. Plaintext tokens and application keys are never logged.

## Recovery after deployment

1. Deploy the latest `worktree` source.
2. Run:

```bash
php artisan optimize:clear
php artisan queue:restart
```

3. Use **Resend invitation** once for each affected invited platform operator.
4. Process the queued delivery and use the newest email link.

A token that was already deleted or belongs to another database cannot be reconstructed. Resend creates a new stable invitation for that operator.

## Verification coverage

Regression coverage verifies stable digest behavior across application keys, legacy fallback, key-rotation inspection, current-token preservation, legacy-token replacement, unreadable-delivery cancellation without link invalidation, safe UI guidance, and sanitized lookup logging.
