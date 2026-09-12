# Base-rent billing contract

Implemented against `worktree-0.0.8`, reviewed baseline `562ac72116e9f4d68cf65a22be4d3a5a401f113e`; updated 2026-09-12.

## Scope and policy

The named `actual_calendar_days_v1` calculation can now create a **base-rent invoice draft**, independently for customer and owner agreements. This is an explicit new commercial convention authorized by the user's research/decision instruction, not proof that all historical TACGL invoices used it. The [calendar calculation contract](base-rent.md) defines daily quantities, anniversary cycles, inclusive dates, partial-cycle denominators and cumulative precision allocation. The billing command requires that named policy; the UI asks the operator to apply it to the selected charge.

Rental owns the immutable base calculation and its covered period. Invoice owns the resulting sales/customer or purchase/supplier document, legal snapshots, balance, lifecycle and source allocation. Tax owns effective tax determination and snapshots. Finance owns account assignments, posting profiles, journals and reversals. Payment continues to own receipts, payments and settlements against the Invoice document. These commands do not create payments or move money automatically.

A customer agreement is charged once for its selected period, independently of how many physical uses or replacements it contains. An owner agreement uses only its own terms. This base-only policy does not compute usage-based KM, driver, AC, OT, night-out, downtime, replacement surcharges or deposit disposition. Those components must not be implied by the generated base line.

## Data and relationships

Two explicit tables, `vehicle_rental_customer_base_charges` and `vehicle_rental_owner_base_charges`, each reference the corresponding agreement. Separate tables preserve a mandatory, tenant-safe agreement foreign key without nullable mutually exclusive references or an unenforced polymorphic agreement ID. They share the small `BaseCharge` model abstraction and billing service because calculation preservation and correction behavior are identical.

Each record preserves the source agreement revision, policy, rate, currency, period and segment breakdown inside the calculation snapshot, plus the resulting six-decimal base amount, creating actor and timestamps. The agreement's activated terms and canonical party/currency identity remain immutable. Tenant-composite foreign keys cover the agreement, organization and actors. There is no reverse agreement-to-invoice foreign key: Invoice already owns its source relationship, so duplicating an invoice pointer would risk disagreement after reissue.

Source IDs use distinct named customer/owner base-charge types. A complete recorded charge has one indivisible source quantity. A new invoice consumes that quantity through Invoice's allocation guard. Source amounts describe the base consideration; Invoice tax and withholding are separately calculated and preserved on the invoice. Source identity is never derived from the agreement reference text.

A void records `voided_at`, `voided_by`, `void_reason` and the incremented charge version. Calculation fields cannot change. Neither charge deletion nor unvoid is supported. The period index is intentionally non-unique: the serialized overlap check rejects active duplicates, while a corrected record may legitimately reuse a voided period.

## Commands and validation

All routes retain authenticated tenant/organization resolution and Rental entitlement. Billing routes additionally require Invoice entitlement. Services enforce the matching customer or owner billing permission and agreement view access. Financial authority is explicit; agreement-edit permission alone does not authorize billing. Existing Invoice permissions control the subsequent approval/posting/reversal UI.

| Command | Route below `/api/v1/vehicle-rental/{kind}/agreements/{agreement}` | Required inputs |
|---|---|---|
| Create base charge and draft | `POST /base-charges` | Agreement `expected_version`, `policy`, inclusive `from`/`until`, `invoice_date`, positive decimal-string `exchange_rate`; optional `due_date` |
| Review charge records and linked documents | `GET /base-charges` | Pagination; trusted context |
| Reissue unchanged charge | `POST /base-charges/{charge}/reissue` | Agreement `expected_version`, document date/exchange inputs |
| Void released charge | `POST /base-charges/{charge}/void` | Agreement `expected_version`, `expected_charge_version`, nonblank `reason` |

Only Active or Closed agreements can be billed. Draft terms remain estimable but cannot create a financial obligation. Dates must have strict `YYYY-MM-DD` format; the complete selected period must fit the agreement. A due date cannot precede its invoice date. Unknown rates fail; zero remains a valid recorded rate but produces no base-rent invoice or period consumption. Charges exceeding the monetary storage precision must be split into smaller periods. The exchange rate is explicit and must be verified for the accounting context; no market rate is guessed.

New charges acquire the agreement row lock before calculating or checking overlap. Overlap is inclusive: an existing charge ending on October 6 conflicts with another starting October 6; October 7 is adjacent. A successful creation stores the charge and creates the Invoice draft in one database transaction. Any Tax, Invoice or posting-plan preparation failure rolls everything back. Repeating a successful create request returns a conflict rather than creating another charge. The review endpoint provides the resulting document after an uncertain client response.

Reissue locks the same agreement and charge, reads the original amount and period, and delegates live-consumption rejection to Invoice. It never recalculates the base amount or trusts client-supplied replacement rates/totals. New invoice date, exchange rate and effective tax snapshots belong to the new financial document. Historical invoices remain available. Cancelled, void and reversed invoices release Invoice source consumption; paid or otherwise live invoices do not.

A wrong base-charge period requires a void, not a silent edit. Every linked invoice must first be cancelled or reversed through Invoice. The void command locks the agreement and charge, validates both supplied revisions, records actor/reason/time, and frees that period for a corrected charge. Reissue of a voided charge fails. This preserves the original calculation and all old financial documents while allowing an actual correction path.

## Tax and posting

`TaxedSourceInvoiceFactory`, owned by Invoice, prepares source lines using Tax's configured party/group/effective-date rules. Manual invoices use the same preparation path, avoiding duplicated arithmetic. `TaxAmountData::toArray()` supplies one tax-component snapshot representation.

Inclusive tax stays inside the line gross. Withholding is removed from payment once through the Invoice adjustment. For the posting plan, ordinary tax is total tax components minus withholding; revenue/expense is payable plus withholding minus ordinary tax. This avoids both counting withholding as output/input tax and posting inclusive gross as untaxed revenue. Synthetic regression rates test this arithmetic; no statutory percentage or threshold is seeded by Rental.

Customer drafts use Invoice Sales/Outbound with the customer party and the shared Finance `CustomerRentalInvoice` / `RentalRevenue` abstractions. Owner drafts use Invoice Purchase/Inbound with the supplier party and `SupplierRentalInvoice` / `RentalExpense`. These are current owner-module contracts; the retired historical Invoice Rental type remains prohibited. No removed Rental implementation is restored.

Finance account/profile assignments are tenant configuration. Missing assignments must fail governed posting; Rental does not invent account numbers or bypass validation. Draft creation prepares a balanced plan but does not post a journal. Existing Invoice approval/posting commands execute the financial effects. Integration tests exercise both sides' approval, posting, reversal and unchanged-source reissue on SQLite using explicit test-only account mappings.

## Operator workflow

Agreement review contains a collapsed **Bill base rent** panel with selected period, invoice/due dates, exchange rate and explicit policy acceptance. Submission creates a draft and links to Invoice review. The recorded-charge list displays periods, currency, amount, document numbers and statuses with pagination. Reissue and void are offered only when every linked invoice is released; the backend independently rechecks this state. Void requires its own reason and confirmation. No foreign keys are manually typed.

The separate base-rent estimate remains read-only. A preview alone does not reserve a period, create source consumption or authorize posting.

## Verification limits

Automated coverage includes same-side overlap, independent sides, stale revisions, unknown/zero and precision handling, immutable values, cancellation/reissue, recorded voids, tenant/permission/feature enforcement, tax/plan reconciliation and actual Invoice-to-Finance posting/reversal in SQLite. These tests are not a real InnoDB contention run, an installed-production migration exercise, or human UAT. Two new table migrations require execution before enabling billing. Existing table migrations and historical financial records are not rewritten.
