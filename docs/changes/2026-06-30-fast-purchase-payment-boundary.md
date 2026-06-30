# Purchase financial ownership corrections

Date: 2026-06-30

## Purpose

Correct the Fast Purchase supplier-payment path and remove Finance identity authority from Purchase header adjustments.

## Supplier payment corrections

- Fast Purchase no longer exposes Finance cash or bank accounts in its context response.
- The frontend no longer asks users to select or submit an internal Finance account.
- The request contract explicitly prohibits payment-level and payment-line Finance account IDs.
- Payment lines contain only payment-method, amount, reference, and external instrument facts.
- The removed `internalBankAccountId` constructor contract is not restored.
- Payment method requirements use the canonical `requires_instrument_details` field.
- Payment-method direction, reference, and instrument requirements are validated before document creation.
- Supplier payments continue through Payment-owned create, submit, approve, post, and allocation services.
- Fast Purchase no longer creates a second Finance journal for the same Payment.

## Purchase adjustment corrections

- Purchase adjustment DTOs contain business facts only.
- Clients cannot supply Finance posting profile IDs, Finance account IDs, cost/tax treatments, mapping provenance, or override reasons.
- Adjustment type is the authoritative source for Purchase recognition policy.
- Purchase persists an immutable recognition snapshot using `cost_treatment`, `tax_treatment`, and `recognition_source`.
- Finance resolves the actual account from the posting profile semantic key.
- Purchase no longer imports or queries Finance account/profile models for adjustment recognition.
- The frontend displays server-owned recognition guidance and does not expose accounting overrides.
- The canonical schema drops obsolete adjustment Finance identity columns and renames `mapping_source` to `recognition_source`.

## Ownership

```text
Purchase business facts
→ Purchase recognition policy
→ Finance semantic posting key
→ Finance effective account resolution
→ immutable Finance journal
```

```text
Purchase payment facts
→ Payment draft and lifecycle
→ Payment posting request
→ Finance semantic account resolution
→ immutable Finance journal
```

Purchase does not select or persist Payment settlement accounts or adjustment Finance account identities.

## Verification scope

Focused source-contract tests guard both backend and frontend ownership boundaries. The connector environment cannot execute Composer, MySQL, PHPUnit, TypeScript, Vitest, or Vite, so complete runtime verification remains a local release gate and is not claimed here.
