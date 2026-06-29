# Fast Purchase payment boundary correction

Date: 2026-06-30

## Purpose

Correct the Fast Purchase supplier-payment path after the Payment financial trust redesign.

## Corrections

- Fast Purchase no longer exposes Finance cash or bank accounts in its context response.
- The frontend no longer asks users to select or submit an internal Finance account.
- The request contract explicitly prohibits payment-level and payment-line Finance account IDs.
- Payment lines contain only payment-method, amount, reference, and external instrument facts.
- The removed `internalBankAccountId` constructor contract is not restored.
- Payment method requirements use the canonical `requires_instrument_details` field.
- Payment-method direction, reference, and instrument requirements are validated before document creation.
- Supplier payments continue through Payment-owned create, submit, approve, post, and allocation services.
- Fast Purchase no longer creates a second Finance journal for the same Payment.
- Focused source-contract tests guard the backend and frontend ownership boundary.

## Ownership

```text
Purchase business facts
→ Payment draft and lifecycle
→ Payment posting request
→ Finance semantic account resolution
→ immutable Finance journal
```

Purchase does not select or persist the Payment settlement account.

## Deferred owner correction

Purchase header-adjustment accounting still accepts and resolves Finance profile/account overrides. That larger Purchase/Finance ownership issue is intentionally excluded from this cohesive payment-boundary change and must be corrected in the next accounting-foundation batch without compatibility parameters.

## Verification scope

The branch was reviewed through exact current-source contracts and focused regression source tests. The connector environment cannot execute Composer, MySQL, TypeScript, Vitest, or Vite, so complete runtime verification remains a local release gate and is not claimed here.
