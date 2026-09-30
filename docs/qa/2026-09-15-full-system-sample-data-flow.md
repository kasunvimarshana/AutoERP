# AutoERP Full-System Sample Data Flow and Expected Results

Date: 2026-09-15

## Purpose

This is a manual end-to-end acceptance test pack for the currently implemented AutoERP tenant workspace. It uses one connected business story so Purchase, Inventory, Vehicle Service, Invoice, Payment, Finance, Reporting, Vouchers, and Audit can be reconciled against the same source transactions.

This is a functional test dataset, not legal or accounting advice. The `TEST VAT 18%` tax below is deliberately configured as test master data; it must not be treated as a statement of the current statutory rate.

## Test rules

- Use a fresh test tenant/database, never production data.
- Use `LKR` as the base currency and amounts with two decimals in the UI.
- Use transaction date `2026-09-15` unless a case specifies another date.
- Record every generated document number. A generated number may differ from the examples, but its prefix/format, uniqueness, and links must be correct.
- Never type database IDs. Select related records by their human-readable names/codes.
- After every write, refresh the detail page and confirm the saved values remain.
- For records with `row_version`/expected-version protection, open the same record in two browsers for the concurrency test.
- Run the flow in the stated order because later expected values depend on earlier transactions.

## 1. Users, roles, tenant, and organization context

Create or assign these test users:

| User | Role | Required scope |
|---|---|---|
| `admin.qa@autoerp.test` | Tenant Admin | All tenant and organization-unit permissions |
| `purchase.qa@autoerp.test` | Purchase Officer | Supplier, Purchase, Inventory lookup, supplier payment create/view |
| `service.qa@autoerp.test` | Service Advisor | Customer, Vehicle, Vehicle Service, service invoice/receipt |
| `technician.qa@autoerp.test` | Technician | Allowed service-job/workforce access only |
| `finance.qa@autoerp.test` | Finance Officer | Invoice, Payment, Finance, Tax, Voucher, Reporting |
| `viewer.qa@autoerp.test` | Read-only Viewer | View permissions only |

Use organization unit `Colombo Main Workshop` (`CMB-MAIN`) and warehouse `Main Parts Warehouse` (`WH-MAIN`). If platform administration is in test scope, create a separate tenant named `QA Auto Care (Pvt) Ltd`, assign a test plan and domain, activate it, and confirm tenant login works only through the valid tenant context.

Expected results:

- Tenant users cannot open platform-administration pages.
- The platform operator can see platform tenants/plans/defaults/operators/sessions/audit/health according to platform permissions.
- Switching organization unit changes the active context and data scope without logging the user out.
- `viewer.qa` can open permitted lists/details but create, edit, post, cancel, reverse, allocate, and delete actions are hidden or rejected with `403`.
- Revoking a user session removes that session without affecting unrelated users.

## 2. Reference, configuration, and UOM master data

Create or verify:

| Area | Sample data | Expected result |
|---|---|---|
| Country | `Sri Lanka (LK)` | Active and available in controlled lookups |
| Currency | `Sri Lankan Rupee (LKR)` | Active; base/test transaction currency |
| Language/timezone | `English`, `Asia/Colombo` | Available in configuration/profile selectors |
| UOM | `Each (EA)`, `Litre (L)`, `Hour (HR)`, `Job (JOB)` | Active and selectable for appropriate items |
| UOM conversion | `1 L = 1000 mL` | Convert `2.5 L` to `2500 mL`; reverse conversion returns `2.5 L` |
| Tenant configuration | A harmless test setting at tenant scope | Resolved value equals tenant entry |
| Organization configuration | Override the same setting for `CMB-MAIN` | Resolved value equals organization override |

Update the organization override once, inspect history, then roll it back.

Expected results:

- Configuration resolution follows the displayed precedence.
- History retains the original and changed values; rollback creates a new history event rather than deleting history.
- Deactivated reference/UOM values disappear from new-entry lookups but remain readable on historical records.
- Duplicate codes and invalid conversion factors are rejected with validation feedback.

## 3. Warehouses and locations

Create:

| Warehouse | Location | Purpose |
|---|---|---|
| `WH-MAIN - Main Parts Warehouse` | `A-01 - Fast Moving Parts` | Filters |
| `WH-MAIN - Main Parts Warehouse` | `LUBE-01 - Lubricants` | Engine oil batches |
| `WH-RET - Returns Warehouse` | `RET-01 - Supplier Returns` | Return checks |

Mark `WH-MAIN` as the default where the UI supports it.

Expected results:

- Locations display their warehouse name, not a raw warehouse ID.
- Default warehouse/location appears automatically in relevant inventory/service forms.
- A warehouse/location with dependent stock cannot be silently deleted; deactivate it and confirm historical transactions remain readable.

## 4. Item, pricing, tracking, and commission master data

Create categories `Parts`, `Lubricants`, and `Labour`; brand `Denso Test`; then create:

| Code | Name | Type / base UOM | Tracking | Purchase cost | Service/sale price | Other setup |
|---|---|---|---|---:|---:|---|
| `PART-OF-001` | Oil Filter Test | Stock item / EA | None | 2,000.00 | 3,500.00 | Reorder level 3 EA |
| `LUBE-5W30` | Engine Oil 5W30 Test | Stock item / L | Batch | 3,000.00 | 4,000.00 | Reorder level 5 L |
| `LAB-OIL-CHANGE` | Engine Oil Change Labour | Labour / HR | None | 0.00 | 5,000.00 | Employee commission 10% |
| `LAB-WASH` | Body Wash Labour | Labour / JOB | None | 0.00 | 2,500.00 | Employee commission fixed 300.00 |
| `COMBO-OIL-SVC` | Standard Oil Service Pack | Combo/bundle | None | Derived | Derived | Children: 1 filter, 4 L oil, 2 HR oil-change labour |

Set the Vehicle Service supervisor default commission to `2%`.

Expected results:

- Item lookups show code, name, UOM, and meaningful availability/pricing context.
- Selecting a service/labour item in a service job autofills its current service price and UOM.
- Changing an item price or commission policy later does not rewrite historical invoice/job/assignment snapshots.
- The combo displays as a meaningful group; child lines cannot be edited through actions that are intentionally reserved for top-level lines.
- Invalid negative prices, commission above 100%, and duplicate item codes are rejected.

## 5. Supplier master data

Create supplier:

- Name: `Lanka Auto Supplies Test (Pvt) Ltd`
- Type/status: `Company / Active`
- Registration: `PV-QA-1001`
- Tax number: `TAX-QA-SUP-001`
- Primary contact: `Nimali Perera`, `+94770000001`, `nimali.supplier@example.test`
- Registered address: `100 Supplier Road, Colombo 10`
- Bank account: `QA Bank`, account ending `1001`
- Category: `Parts Supplier`
- Credit terms: `30 days`
- Add one harmless PDF/image test document with an expiry date.

Expected results:

- Supplier code is generated/reserved once and remains unique.
- Detail view shows structured contacts, addresses, bank accounts, categories, credit/tax information, and document status.
- Status changes appear in status history.
- Deactivation removes the supplier from new purchasing lookup results but does not alter prior purchase documents.

## 6. Customer and vehicle master data

Create customer:

- Name: `Kasun Motors QA Customer`
- Type/status: `Company / Active`
- Mobile: `+94770000002`
- Email: `accounts.customer@example.test`
- Preferred channel: `WhatsApp`
- Billing/service address: `25 Customer Avenue, Colombo 05`
- Category: `Fleet Customer`
- Credit limit: `100,000.00`; credit days: `15`
- Add contact, bank-account, and test-document records.

Create vehicle master data `Toyota`, `Passenger Car`, `Sedan`, `Corolla`; then create:

- Registration: `CAB-1234`
- Customer/current owner: `Kasun Motors QA Customer`
- Ownership: `Customer Owned`
- Model: `Toyota Corolla`
- Year: `2020`
- Fuel/transmission: `Petrol / Automatic`
- VIN: `QAUTOERPVIN000001`
- Odometer: `50,000 km`
- Status: `Active`
- Attribute: `Colour = Silver`
- Add registration and insurance test documents.

Expected results:

- Customer and vehicle codes are unique and human-readable.
- Customer list shows `CAB-1234` as the current vehicle where applicable.
- Vehicle ownership/current-owner history is correct; only one ownership is current.
- Vehicle document preview/download works according to permission.
- WhatsApp verification moves through a controlled status and never exposes a challenge secret in ordinary screens/logs.
- Deactivated customers/vehicles are excluded from new eligible lookups but remain visible in history reports.

## 7. HR and workforce

Create department `Workshop`, designations `Service Supervisor` and `Technician`, skill `Engine Service`, and certification `Workshop Safety`.

Create employees:

| Code/name | Designation | Availability | Rate | Skill/certification |
|---|---|---|---|---|
| `EMP-SUP-01 - Saman Supervisor` | Service Supervisor | Available | Monthly 100,000.00 | Workshop Safety |
| `EMP-TECH-01 - Amal Technician` | Technician | Available | Hourly 1,500.00 | Engine Service: Expert |
| `EMP-TECH-02 - Bimal Technician` | Technician | Available | Hourly 1,200.00 | Engine Service: Advanced |

Add an address, contact, document, availability record, and certification/license/skill assignment where supported.

Expected results:

- Service workforce picker shows active, eligible people by name/code/designation and excludes invalid/unavailable assignments.
- The inline employee picker opens as a page column, remains searchable/paginated, and closes explicitly.
- Employee status/availability histories remain intact.
- Expired certification/license data is visible historically and can prevent eligibility where the configured rule requires it.

## 8. Purchase-to-stock flow

### 8.1 Purchase order

Create PO for `Lanka Auto Supplies Test`:

| Line | Quantity | Unit price | Net |
|---|---:|---:|---:|
| Oil Filter Test | 10 EA | 2,000.00 | 20,000.00 |
| Engine Oil 5W30 Test | 20 L | 3,000.00 | 60,000.00 |

Subtotal `80,000.00`; apply configured `TEST VAT 18% = 14,400.00`; expected total `94,400.00`. Save draft, submit/pending approval if exposed, approve, and later close after receipt.

Expected results:

- Status follows `Draft → Pending Approval → Approved → Closed` as supported by the page/actions.
- Approval cannot occur twice; cancellation/closed rules are enforced.
- PO PDF/share output shows supplier, organization-unit legal identity, line/UOM values, tax, total, dates, and document number.

### 8.2 Goods receipt and batch tracking

Receive the full PO into `WH-MAIN`:

- Oil filters: `10 EA` to `A-01`.
- Engine oil: `20 L` to `LUBE-01`; click **Add batch**.
- Expected suggested internal batch pattern: `BAT-20260915-######` (sequence digits may vary).
- Replace or keep the editable suggestion; supplier lot: `SUP-LOT-5W30-01`; expiry: `2028-09-30`.

Post the GRN.

Expected results:

- GRN follows `Draft → Posted`; posting is atomic and cannot duplicate stock on retry.
- On-hand: filters `10 EA`; oil `20 L` in the selected batch/location.
- Stock movement report contains receipt movements referencing the GRN.
- Inventory valuation/cost layers increase by `80,000.00` before tax, subject to the configured costing/landed-cost rules.
- GRN payable report shows the posted receipt/supplier linkage and the correct payable basis.
- Reversal, if tested on a separate GRN, produces reversing movements; it never edits/deletes original movement history.

### 8.3 Supplier invoice and partial payment

Create the supplier invoice from the PO/GRN for `94,400.00`, due `2026-10-15`. Post it. Create an outbound supplier payment of `50,000.00` using a valid payment method and external reference `QA-SUP-PAY-001`; complete its required submit/approve/post/allocation lifecycle.

Expected results:

- Supplier invoice status becomes `Posted`, then `Partially Paid`/balance `Partial` after allocation.
- Accounts payable before payment: `94,400.00`; after payment: `44,400.00`.
- Payment allocation is `Fully Allocated` when the entire 50,000 is assigned to this invoice.
- Payment creates only one authoritative Finance posting; Purchase does not duplicate it.
- Payment/voucher detail and print output show supplier and external instrument facts, not internal database IDs.

### 8.4 Purchase return and debit note

Return `2 EA` oil filters against the posted source document. Goods value `4,000.00`; test tax `720.00`; expected supplier credit/debit-note effect `4,720.00`. Approve/post the return and related debit note.

Expected results:

- Filter on-hand becomes `8 EA`.
- Original receipt remains unchanged; a separate outbound/return movement references the return.
- Remaining payable becomes `39,680.00` (`94,400 - 50,000 - 4,720`).
- Return cannot exceed the returnable quantity. A second attempt for more than `8 EA` is rejected.
- Also test a small manual supplier return in a separate scenario; its source/reference requirements and approvals must be explicit.

### 8.5 Fast Purchase alternative path

On a separate document, fast-purchase `1 EA` filter for `2,000.00 + 360.00 test VAT = 2,360.00` and pay it immediately.

Expected results:

- One operation creates the correct Purchase, stock, supplier invoice/payment/allocation, and Finance effects without asking the user for internal Finance account IDs.
- Retrying with the same idempotency key does not create duplicates.

## 9. Inventory independent workflows

Run these after the return and before service issue:

- Opening stock import: import a CSV for a separate test item/location; confirm valid rows post and invalid/duplicate rows return row-specific errors without silent loss.
- Positive adjustment: add `1 EA` filter with reason `QA count gain`; expected filter on-hand `9 EA`.
- Negative adjustment: remove `1 EA` with reason `QA damage`; expected filter on-hand `8 EA`.
- Transfer: move `1 EA` filter from `A-01` to another valid `WH-MAIN` location and confirm total warehouse quantity stays `8 EA`.
- Stock count: count one location with a deliberate variance and confirm approval/posting creates a separate adjustment movement.
- Reservation/allocation: reserve a separate quantity, confirm available is reduced but on-hand is not; release it and confirm availability restores.
- Batch/serial: search `SUP-LOT-5W30-01`, verify status/expiry/current quantity; invalid duplicate batch identity is rejected.
- Cost adjustment: apply to a separate test layer and verify a new valuation adjustment without editing original receipt cost.

Expected results:

- Every stock change has direction, movement type, quantity, warehouse/location, item, source reference, actor, and timestamp.
- On-hand, reserved, available, and valuation are distinct and reconcile across Inventory screens and reports.
- Negative stock/over-allocation is rejected where policy disallows it.

## 10. Vehicle Service connected flow

### 10.1 Job creation and inspection

Create a `Full Service` job for `CAB-1234`, bill to `Kasun Motors QA Customer`, supervisor `Saman Supervisor`, odometer `50,500 km`, complaint `Engine oil service and filter replacement`, promised completion `2026-09-15 17:00`.

Expected initial result:

- A unique job number is generated.
- Status is the initial draft/open state exposed by the application.
- Supervisor commission snapshot is `2%`; the job retains it even if the default changes later.
- Vehicle, customer, model, supervisor, and organization unit display as labels, not IDs.

Add inspection facts: diagnosis `Oil due for replacement; filter restricted`, work recommendation `Replace filter and 4 L oil`, and attach one test inspection image/document. Move the job through inspect/start actions.

Expected results:

- Status history records every transition, actor, and time.
- Required inspection/workforce rules block invalid transition and show a clear error.
- Vehicle status/current job context updates consistently if the workflow owns such a transition.

### 10.2 Job lines and workforce

Add lines:

| Line | Qty | Price | Line total |
|---|---:|---:|---:|
| Engine Oil Change Labour | 2 HR | 5,000.00 | 10,000.00 |
| Oil Filter Test | 1 EA | 3,500.00 | 3,500.00 |
| Engine Oil 5W30 Test | 4 L | 4,000.00 | 16,000.00 |

Subtotal before job discount: `29,500.00`.

Assign `Amal Technician` and `Bimal Technician` to the labour line with the same snapshotted `10%` policy. The line commission pool is `1,000.00`; expected equal split is `500.00` each. Cancel one assignment in a separate copy of the job and confirm the cancelled employee earns `0.00` while the active recipient receives the recalculated pool.

Expected results:

- Line UOM and prices autofill but remain subject to authoritative backend validation.
- Two equal-policy employees split one pool; commission is not multiplied to 2,000.
- Workforce updates bump the job version and preserve cancelled assignment history.

### 10.3 Inventory issue

Issue `1 EA` filter from `A-01` (or its transferred location) and `4 L` oil from batch `SUP-LOT-5W30-01`/`LUBE-01`.

Expected results:

- After issue: filters `7 EA` total; oil batch `16 L`.
- Service issue stock movements reference the job and selected lines/batch/location.
- Expected material cost/COGS for this job is `14,000.00` (`2,000 + 4 × 3,000`) before later valuation adjustments.
- A second issue of the same required quantity is prevented; insufficient batch/location stock returns a controlled validation error.

### 10.4 Discount, completion, and invoice

Apply fixed job discount `1,000.00`. Discounted pre-tax amount `28,500.00`; `TEST VAT 18% = 5,130.00`; expected invoice total `33,630.00`.

Complete the job and create/post the service invoice.

Expected results:

- Invoice preview and posted invoice agree exactly: subtotal `29,500.00`, discount `1,000.00`, taxable amount `28,500.00`, tax `5,130.00`, total/balance `33,630.00`.
- Service invoice contains snapshotted customer/vehicle/legal/address/line/tax facts and remains stable if master data later changes.
- Invoice status is `Posted`; balance status is `Unpaid`; AR increases by `33,630.00`.
- Material cost/COGS posting is `14,000.00`; revenue excluding test VAT is `28,500.00`; gross profit contribution is `14,500.00`, subject to the canonical report's classification.
- Supervisor commission is `672.60` if the configured rule applies 2% to the documented job grand-total base (`33,630 × 2%`). If the UI policy explicitly displays a different authoritative base, record that displayed base and verify the saved snapshot/calculation against it.
- Technician earned pool is `1,000.00`, `500.00` each for the two active equal-policy assignments.
- PDF/print output contains readable customer, vehicle, organization, totals, and legal document identity.

### 10.5 Customer receipts and settlement

Receive `20,000.00` using Cash/reference `QA-CUST-REC-001` and allocate it to the service invoice. Then receive/allocate `13,630.00` using Bank Transfer/reference `QA-CUST-REC-002`.

Expected results:

- After first receipt: invoice status `Partially Paid`, balance `13,630.00`, AR `13,630.00` for this invoice.
- After second receipt: invoice status/balance `Paid`, balance `0.00`, AR contribution `0.00`.
- Each receipt follows Payment-owned lifecycle and produces exactly one Finance posting.
- Any unapplied amount is shown separately as customer credit; it is not silently treated as revenue.
- Receipt voucher/print and WhatsApp share link/message contain the correct human-readable document/customer facts and no private storage path.

### 10.6 Cancellation and reversal branch

Use a separate copied test job/document chain:

- Cancel a job before invoicing and confirm the preview explains affected lines, assignments, reservations/issues, and commissions.
- Reverse a posted payment allocation and confirm the original allocation remains with a separate reversal.
- Reverse/void only when allowed; confirm invoice/payment/ledger balances return to their prior values.

Expected results:

- Historical records are never edited away.
- Cancelled commission and labour remain reportable historically but do not count toward effective totals/rankings.
- Posted financial history is corrected by reversal entries, never by changing the original ledger entries.

## 11. Invoice, Payment, cheque, and Voucher coverage

In addition to the connected flow:

- Create a small manual outbound invoice and a manual inbound invoice/credit/debit scenario; exercise draft, approve, post, partial/full settlement, cancel/reverse/void eligibility.
- Create inbound and outbound payment methods for Cash, Bank Transfer, and Cheque. Confirm direction and instrument requirements.
- For cheque payment, enter cheque number/bank/date, preview through a cheque template, mark printed, then test the valid clear/bounce/return path on separate cheques.
- Test a customer advance larger than its immediate allocation. Confirm unapplied balance moves `Available → Partially Applied → Fully Applied`, or `Refunded` on the refund branch.
- Open Vouchers list/detail/print for service receipt, supplier payment, and any supported source types.

Expected results:

- Document, posting, allocation, and instrument statuses are shown separately and consistently.
- Duplicate create/post requests are idempotent.
- Allocation never exceeds payment availability or invoice eligibility.
- Reversal/refund restores balances exactly and retains the original payment/allocation.
- Vouchers resolve their source document and party labels without raw IDs.

## 12. Finance, tax, and reconciliation

### Finance setup

Verify chart of accounts, semantic account roles, assignments, and posting profiles for Inventory, COGS, Sales/Service Revenue, Output Test Tax, Input Test Tax, AR, AP, Cash, and Bank. Do not choose these internal accounts from Purchase/Service business forms; Finance resolves them from configuration.

### Expected connected-flow ledger effects

Use the ledger and source documents to confirm at minimum:

| Event | Debit | Credit |
|---|---:|---:|
| Supplier invoice/receipt recognition | Inventory/input-tax or configured purchase recognition `94,400.00` | AP `94,400.00` |
| Supplier payment | AP `50,000.00` | Cash/Bank `50,000.00` |
| Purchase return/debit note | AP `4,720.00` | Inventory/input-tax reversal `4,720.00` |
| Service invoice revenue/tax | AR `33,630.00` | Service/parts revenue `28,500.00` + output tax `5,130.00` |
| Service material cost | COGS `14,000.00` | Inventory `14,000.00` |
| Customer receipts | Cash/Bank `33,630.00` | AR `33,630.00` |

Exact account split is governed by active posting profiles; total debits must equal total credits for every posted journal.

Also test:

- Manual journal: debit Repairs Expense `1,000.00`, credit Cash `1,000.00`; post, then reverse it on the next date.
- Closed accounting period: attempt a posting and confirm rejection; post in an open period successfully.
- Trial balance: total debits equal total credits.
- Account balances/general ledger: drill-down source links resolve correctly.
- Budget: Repairs Expense budget `10,000.00`; after manual journal, actual `1,000.00`, variance `9,000.00` before reversal; after reversal net actual returns as defined by report date range.
- Bank reconciliation: statement line `13,630.00` reference `QA-CUST-REC-002`; match it to the bank receipt, unmatch, rematch, complete; completed reconciliation retains its match history.
- Currency revaluation: use a separate foreign-currency test balance and verify a balanced unrealized gain/loss journal.
- Tax reports: input/output tax, liability and reconciliation agree with source documents and date filters.

## 13. Reports and dashboard expected outputs

Filter all reports to `2026-09-15` and `CMB-MAIN` after the connected flow is complete.

Expected minimum results:

- Business Overview: period revenue includes the posted service invoice under the canonical ledger definition; receivables from it are `0.00` after full settlement; remaining payable from the main purchase chain is `39,680.00`; active service jobs exclude the completed job.
- Profitability Overview: service-chain revenue excluding test VAT `28,500.00`, material COGS `14,000.00`, gross-profit contribution `14,500.00`, plus/minus any other transactions in the chosen clean dataset. Other expense includes the `1,000.00` manual journal only for a range before its reversal netting effect.
- Revenue trend/cash flow: service invoice appears on the authoritative revenue date; customer cash-in totals `33,630.00`; supplier cash-out totals `50,000.00`.
- Inventory health: filter quantity `7 EA`, oil batch `16 L` before independent stock-count differences; filter is not low stock against reorder level 3. Use another item below its threshold to verify the low-stock action.
- AR/AP aging: settled customer invoice is absent from open AR; supplier open amount `39,680.00` appears in the correct due-date bucket.
- Employee Commission: Amal `500.00`, Bimal `500.00`, supervisor `672.60` where the report includes supervisor commission; earned/pending/cancelled/effective values follow actual job lifecycle.
- Employee Performance: two technicians show the completed/assigned job, labour hours/value and effective commissions; ranking excludes cancelled values.
- Vehicle Service History: `CAB-1234` shows the completed job, complaint, diagnosis, work, parts, staff, invoice `33,630.00`, and settled balance `0.00`; clearing vehicle filter returns all recent vehicles.
- Detailed Vehicle Service/Technician Work/Profitability: job number links to the job; issued parts cost `14,000.00`; labour and technician values reconcile.
- Purchase detailed/GRN Payables: PO, GRN, invoice, payment, return/debit-note, and remaining `39,680.00` reconcile.
- Stock Movement: receipt `+10 filter/+20 L oil`, return `-2 filter`, adjustments/transfers, and service issue `-1 filter/-4 L oil` are individually visible.
- Export: CSV/XLSX/PDF exports use the active filters. If export truncation limits are reached, the UI clearly reports truncation metadata.

If the database contains other transactions, do not expect dashboard totals to equal only the numbers above. Reconcile by filtering to this test organization/date/reference, or use a fresh tenant.

## 14. Vehicle Rental conditional coverage

Vehicle Rental routes and implementation exist, but the module is currently removed from the tenant sidebar. Test it only if the tenant feature and direct `/vehicle-rental/*` route are intentionally enabled for acceptance.

Use two agreements:

- Customer/lessee agreement for `Kasun Motors QA Customer`.
- Owner/lessor agreement for a test supplier-owned vehicle.

Cover:

- Draft agreement, rate versions (daily/monthly, AC modes, excess km, driver/overtime/night-out), activation requirements, and close.
- Owner-supply and customer-use assignments: `Planned → Active → Returned`; separate replacement and cancellation branches.
- Custody events: handover, return, replacement-out, replacement-in with odometer continuity.
- Daily running chart create/update/finalize; finalized data cannot be silently edited; reversal is a separate event.
- Customer and owner calculations, cancellation, financial-document creation, invoice/payment handoff, deposit allocation/refund/forfeiture where available.
- Rental summary report and legal document output.

Expected results:

- Assignment dates stay inside agreement coverage and one vehicle cannot have conflicting active allocation.
- Customer receivable and owner payable calculations remain separate.
- Every financial document links back to the agreement/calculation and posts once.
- Hidden sidebar navigation does not bypass route permission/feature enforcement.

## 15. Audit, security, isolation, and failure tests

Confirm audit logs for master-data create/update/status changes, approvals/posts/reversals, configuration rollback, access changes, service lifecycle, inventory movements, and payments. Open one audit detail and confirm actor, tenant, organization unit, action, source, time, and sanitized before/after context.

Negative/security tests:

- Login with wrong password gives a safe error without stack trace or account-secret leakage.
- Tenant A cannot read or mutate Tenant B data even by changing a URL parameter.
- An organization-unit-restricted user cannot access another unit's records.
- Uploaded private documents require authorization; copied storage URLs do not become public access.
- Invalid IDs, invalid enum/status values, malformed dates, negative amounts, excessive quantities, and missing required relationships return predictable validation errors.
- Deleted/deactivated related records do not cause historical screens or reports to expose raw IDs.
- Sensitive values, tokens, secrets, passwords, and document paths do not appear in audit payloads or API error responses.

## 16. Concurrency and atomicity tests

Run with Browser A and Browser B on the same record:

1. Open the same vehicle-service job in both browsers.
2. Browser A adds/updates a line and saves successfully.
3. Browser B submits its stale form.
4. Expected: Browser B receives an explicit version/conflict response, refreshes, and does not overwrite A.

Repeat the pattern for any UI exposing expected-version protection, including commission policies and other versioned records. Also submit the same payment/GRN/invoice action twice rapidly.

Expected results:

- Exactly one logical document/posting/movement is created for an idempotent retry.
- No partial state remains if any step fails: stock, source status, invoice/payment, journal, allocation, and audit either commit together as designed or roll back.
- Concurrent automatic batch/sequence requests return different numbers; gaps are acceptable, duplicates are not.

## 17. Final acceptance reconciliation

The connected main flow passes only when all of these agree:

| Check | Expected |
|---|---:|
| Main PO total | 94,400.00 |
| Supplier payment | 50,000.00 |
| Purchase return/debit-note effect | 4,720.00 |
| Remaining supplier payable | 39,680.00 |
| Filter stock after receipt, return, net-zero adjustments, and service issue | 7 EA |
| Engine-oil batch after service issue | 16 L |
| Service pre-discount subtotal | 29,500.00 |
| Service discount | 1,000.00 |
| Test tax | 5,130.00 |
| Service invoice / receipts total | 33,630.00 |
| Final service invoice balance | 0.00 |
| Service material COGS | 14,000.00 |
| Service gross-profit contribution before other expenses | 14,500.00 |
| Labour commission pool | 1,000.00 |
| Amal / Bimal commission | 500.00 each |
| Trial balance | Total debits = total credits |

Final pass criteria:

- All expected calculations reconcile from source document to stock, invoice/payment, ledger, dashboard, reports, vouchers, and audit.
- All permitted actions succeed and all forbidden/invalid actions fail clearly.
- No raw relationship IDs, duplicate postings, negative unintended stock, stale overwrites, lost history, or cross-tenant data leakage is observed.
- Every failed case leaves the database in its pre-action consistent state.

