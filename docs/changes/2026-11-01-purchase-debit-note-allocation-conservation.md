# Purchase debit-note allocation conservation at the write boundary — 2026-11-01

## Root cause

The Purchase debit-note allocation command checked that the proposed amount was positive and did not exceed the **persisted remaining_amount**, but did not verify that the previously stored `amount`, `allocated_amount`, and `remaining_amount` agreed. On a corrupt historical/imported row where remaining amount was overstated, a valid-looking allocation could reduce the supplier invoice again and further damage the subledger.

## Change within the owning Purchase module

`PurchaseDebitNoteService::allocate()` now validates the locked debit note's monetary conservation **before** invoking the Invoice-owned `allocateCredit()` command. It requires a positive original face amount, nonnegative allocated and remaining amounts and exact equality `allocated + remaining = amount` using existing six-place `DecimalMath` operations. Any inconsistency raises `InvalidArgumentException('Purchase debit note allocation balances are inconsistent.')`; the surrounding DB transaction is not allowed to modify Purchase or Invoice balances. This does not infer a missing currency, permit overallocation, or change Payment/Finance ownership or historical figures.

## Tests and verification scope

Extended the existing Purchase integration allocation test, using its genuine tenant, supplier, posted invoice and partially allocated debit-note fixtures. It simulates historical data corruption (mismatched allocated amount, negative remaining amount and changed original face value) via direct SQL in a disposable `RefreshDatabase` database, checks rejection and unchanged invoice balance and restores its known-good test fixture before the existing currency, party and final-allocation assertions. Those earlier tests are preserved unchanged.

**The updated Laravel suite has not been executed** in this session because the real Windows project and its DB are not mounted in the isolated Linux command environment. This is a committed implementation and regression test, **not** a passing PHP/MySQL claim. Run the targeted Purchase test and full backend suite on this exact candidate before release. The source-currency and Finance historical reconciliation requirements remain independent gates. No paid services, Actions, protected branch writes, production-data modifications or auto-repair were performed.
