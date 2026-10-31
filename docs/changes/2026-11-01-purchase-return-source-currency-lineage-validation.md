# Purchase debit note source lineage validated even with known Return currency — 2026-11-01

## Root-cause code investigation

`PurchaseDebitNoteService::resolveCurrencyId` previously returned immediately whenever a linked `PurchaseReturn` already had `currency_id`. As a result, it **never checked the referenced Goods Receipt and Purchase Order tenant/organization/supplier identities or whether the Return currency contradicted the Purchase Order currency**. The existing `assertSourceContract` only checks the Return against the newly requested debit note; inconsistent historical source chains could therefore be turned into new debit notes without detection.

The bug is real even though the normal `PurchaseReturnService::create` path snapshots currency from the Purchase Order. Historical imports, concurrent upstream changes outside normal workflows or corrupted source records still require the financial document boundary to fail closed. The five read-only historical SELECTs are diagnostic and are not a replacement for source validation.

## Change in owning Purchase module

The source resolution now retains `returnCurrencyId` and validates the linked GRN's tenant, organization and supplier and the linked PO's same scope **before** returning any known currency. If both recorded Return and source Order currencies exist and disagree, debit-note creation fails with a descriptive error. If the Order currency is missing, a separately recorded Return currency is preserved; if the Return currency is missing but the Order supplies proven currency, the Order currency is inherited. Manual supplier returns, which have no GRN lineage and use their explicitly validated currency, retain their existing behavior. Referenced GRN returns with no receipt ID are rejected rather than silently treated as unlinked.

No historical currency is guessed or rewritten. No new foreign key, hardcoded rate, cross-module data repair, Finance posting, or monetary schema change is introduced.

## Tests

Two real `PurchaseEngineTest` cases were added using its existing posted-GRN and Purchase-return fixtures. They deliberately corrupt the source PO currency or GRN supplier **after** the Return has snapshotted a known currency and verify that new debit-note creation fails with the precise source-lineage error, leaving no Purchase debit note. This specifically tests the formerly unverified early-return path.

The tests are **committed but not executed** from this session: the isolated Linux runtime cannot mount the user's Windows checkout, lacks BCMath/PDO drivers and the full Laravel vendor tree. Exact-head SQLite/MySQL execution remains required before production promotion. The targeted local PHP lint performed on `tests/Unit/InvoiceBalancePrecisionTest.php` from the prior same candidate was successful and verified its Git blob hash; it is not a lint/test claim for this Purchase change.

No paid services, GitHub Actions, production data writes, review bypass or release branch merge occurred.
