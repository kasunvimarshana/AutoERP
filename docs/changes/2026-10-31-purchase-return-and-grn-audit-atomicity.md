# Purchase Return and Goods Receipt mutation + audit atomicity — 2026-10-31

## Owner-module defect

Like Purchase debit notes (see `2026-10-31-purchase-debit-note-audit-atomicity.md`), the Purchase Return and Goods Receipt HTTP controllers previously committed a service mutation before invoking the application's required immutable `PurchaseAuditService` event writer. If audit persistence then failed, the HTTP request reported failure while leaving successfully committed inventory, Purchase documents and potentially Finance/Tax results without the corresponding document audit event.

The issue belongs to Purchase HTTP workflow orchestration. The existing Purchase/Inventory/Tax/Finance service boundaries, validation, row locks, idempotency and posting logic remain authoritative.

## Implementation

- `PurchaseReturnController`: transactionally group the existing service operation and Purchase audit event on create, manual supplier return create, approval, post and cancellation (**five** paths).
- `GoodsReceiptNoteController`: transactionally group the existing service operation and Purchase audit event on create, post and reverse (**three** paths).
- `PurchaseDebitNoteController` had already been corrected on the parent integration candidate for four analogous paths.
- All changes use existing `DB::transaction` and nested service transactions; no new abstraction, API field, data migration, finance policy or copied legacy code was introduced.
- UI response/presentation formatting happens outside these new outer transaction scopes. The mutations and audit event are in one transaction on the same application database.

## Regression tests

Expanded the existing Purchase HTTP feature suite with three new fail-closed cases:

1. Goods Receipt creation: a deliberately failing audit event must roll back creation.
2. Goods Receipt posting: audit failure must preserve the draft status, original revision, inventory-movement count and Finance journal count.
3. Purchase Return posting: audit failure must preserve its draft status and revision and leave inventory movements and Purchase debit notes unchanged.

The previously added three Purchase debit-note fail-closed tests are additionally constrained to throw **only on their respective document audit event names**, preventing an unrelated earlier audit call from producing a false-positive rollback test. New tests apply that same event-specific behavior and permit unrelated audit operations.

## Verification scope and boundaries

- Read exact controllers, `RecordAuditEvent`, Eloquent Audit writer, Purchase coordinators, real API routes and existing `PurchaseOrderApiTest` fixture before changing the transaction boundary.
- On the earlier debit-note work, PHP 8.4.24 `php -l` passed on a standalone reproduction of the four changed transaction closures. No full Laravel checkout/vendor dependency tree is present in this execution container; no full Laravel tests were executed on **this** commit.
- The user's 2026-10-31 Windows log showing **958 Laravel / 10,634 assertions and 468 frontend tests** predates the present controller changes. Do not treat it as proof that these new tests passed.
- The new tests are committed and require real SQLite and disposable MySQL regression runs before production promotion. The changes do not repair historical financial data or claim complete Finance/GL settlement reconciliation.
- No paid services, GitHub Actions, unsafe production database mutations, mandatory-check bypass, PR merge or target-branch push were performed.
