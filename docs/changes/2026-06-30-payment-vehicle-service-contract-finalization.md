# Payment and Vehicle Service contract finalization

Date: 2026-06-30

## Purpose

Finalize the public Payment frontend contract and the Vehicle Service receipt boundary after the Payment financial trust redesign.

## Corrections

- Payment method seeding now uses the canonical `requires_instrument_details` field and no longer probes the runtime schema.
- Payment method administration uses `requires_instrument_details`; the removed `requires_bank_account` contract is not preserved.
- Payment frontend types now expose independent document, posting, allocation, and instrument states.
- Payment create requests contain business input only; server-owned numbering, statuses, posting references, cleared values, and arbitrary metadata are not accepted by the frontend contract.
- Payment lifecycle actions send the exact aggregate `row_version` as `expected_version`.
- Refund and reversal numbers remain server-owned. The UI submits only date, amount where applicable, and a mandatory reason.
- Payment entry validates method reference and instrument requirements before submission.
- Shared payment-method fields collect only instrument facts persisted by the Payment request contract.
- Vehicle Service payment options no longer expose or query Finance accounts.
- Vehicle Service receipts submit the exact job version and use Payment method/instrument facts only.
- Vehicle Service historical payment links render immutable Payment snapshots and the four canonical lifecycle dimensions.
- Focused Vehicle Service frontend coverage verifies exact-version submission and absence of internal Finance account selection.

## Ownership

```text
Vehicle Service business facts
→ Payment draft, lifecycle, posting, and allocation
→ Finance semantic account resolution
```

Vehicle Service does not select or persist internal Finance accounts.

## Verification scope

The change was reviewed through repository contracts and focused regression source checks. This connector environment cannot execute Composer, MySQL, TypeScript, Vitest, or Vite, so complete runtime release verification remains a deployment-environment gate and is not claimed by this record.

## Separate unresolved owner issue

Fast Purchase still contains a larger legacy accounting implementation that directly resolves Purchase adjustment accounts and duplicates Payment Finance posting. It requires a complete Purchase-owner refactor; no compatibility parameters or partial account-selection workaround were introduced in this batch.
