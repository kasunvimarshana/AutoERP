# Purchase debit-note party HTTP validation — 2026-10-13

## Follow-up to party-integrity guard

The earlier append-only record `2026-10-13-purchase-debit-note-supplier-party-integrity.md` describes Purchase service creation/allocation rejection for non-supplier party discriminators.

The public `StorePurchaseDebitNoteRequest` must also reject invalid caller input with an actionable **HTTP 422 validation error**, rather than allowing a domain exception to emerge from the service. It now uses the canonical `InvoicePartyType::Supplier` case as its allowable explicit `supplier_type`. The optional field is retained for existing requests; the owning Purchase service normalizes an omitted type to the canonical supplier value.

`PurchaseOrderApiTest` now explicitly checks the HTTP 422 and `supplier_type` validation key for a customer-type debit note targeting a valid supplier ID. The existing Purchase engine test covers the service-level historical persisted-row guard and unchanged invoice balance on rejected allocation.

## Release boundary

This change is limited to party-discriminator integrity and does not resolve Purchase debit-note currency provenance, cross-currency allocation, Finance reconciliation or the Vehicle Rental §R release gates. CI must be evaluated on the final PR candidate SHA before merge. Prior append-only records have not been edited.
