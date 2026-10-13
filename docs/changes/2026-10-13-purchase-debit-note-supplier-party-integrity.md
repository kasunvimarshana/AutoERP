# Purchase debit-note supplier-party integrity — 2026-10-13

## Context

The combined integration candidate for PR #127 contains a Purchase debit-note API and service. A focused follow-up audit found that `StorePurchaseDebitNoteRequest` accepted arbitrary `supplier_type` text, while `PurchaseDebitNoteService::create()` validated a supplier ID but persisted the caller-provided party discriminator unchanged. At allocation, the service checked invoice party type `supplier` and numeric party ID, but did not verify the note's own `supplier_type`. Because IDs are not globally unique across entity types, a record with the same numeric ID but an inconsistent discriminator could reach supplier-invoice credit allocation.

## Root-cause change

- **Purchase** now rejects explicitly non-supplier debit-note party types on creation, using `InvoicePartyType::Supplier` as the canonical discriminator. An omitted/null DTO supplier type remains accepted and is normalized to `supplier`, consistent with the existing optional DTO parameter and the public request default.
- **Purchase allocation** validates the persisted note discriminator before Invoice-owned credit allocation, so even an older inconsistent row is refused without changing balances.
- The existing Purchase engine regression checks both a forged creation discriminator and a persisted posted-note mismatch. It verifies that rejected allocation leaves the note's remaining amount and the supplier invoice balance unchanged.
- Invoice calculation, Invoice credit ledger, Finance posting and Rental operations remain within their respective owner modules and were not altered.

## Verification and limitations

- The candidate previously passed full SQLite/MySQL (919 tests / 10,080 assertions per database), frontend (432 tests), typecheck, lint and build **before** these changes. Those results must not be attributed to this new head.
- PHP syntax and GitHub CI must be checked on the new commit; no production database or financial UAT is exercised by this patch.
- This guard is independent of the larger unresolved Purchase note **currency provenance** and FX integrity blocker in issue #128. It does not complete the Vehicle Rental financial release gates in `docs/vehicle-rental/TODO.md` §R.
- PR #127 remains draft and not approved until independent financial/business/operational release gates are satisfied.

This change record is append-only; earlier verification reports are retained unchanged.
