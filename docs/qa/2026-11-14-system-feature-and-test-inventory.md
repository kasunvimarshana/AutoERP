# AutoERP feature inventory and QA test checklist

**Prepared:** 2026-11-14  
**Basis:** Current checkout's React router, UI pages, Laravel modules/routes, recent `docs/changes` records, and the existing [sample data flow](2026-09-15-full-system-sample-data-flow.md).  
**Scope warning:** This is a source-based test inventory, not proof that every flow works or that the same build is deployed. Confirm tenant entitlements, permissions, and deployed frontend/backend versions before a release run. The 2026-11-12 dashboard incident records describe a deployed frontend/backend contract mismatch.

## How to use this document

- **Smoke:** Run the numbered S cases after every deployment. Use a test tenant, one organization unit, and known master data. A failed critical path blocks release.
- **Regression:** Run the R matrix for affected modules and their upstream/downstream dependencies. Run the whole matrix for a major release.
- For every case, capture build/commit, environment, tenant and organization unit, role, input, generated document number, expected/actual result, and evidence link. Mark **Pass / Fail / Blocked / Not applicable**; do not count a disabled feature as passed.
- Check both UI and resulting API/data state. Refresh after writes. Compare source documents, stock, invoice balance, finance postings, reports, and audit where relevant.
- Test each write with invalid input, a user lacking permission, duplicate submission/retry, and two sessions editing the same record. Failed writes must leave no partial effects.

## 1. Current feature map

This inventory names features visible in the current source. Some screens combine several workflows, and some routes are permission or plan gated. A page's presence does not establish production availability.

| Area | Features and user workflows to cover |
|---|---|
| Identity, tenant, access | Login/logout and session handling; tenant workspace and plan/entitlement; active organization unit; users, roles, permission catalogue; tenant audit logs; platform tenant/plans/configuration/operators/security/audit/health (platform role only). |
| Settings and reference data | Settings and scoped configuration; reference data; UOMs, conversions and conversion tool; document sequencing. |
| Parties and vehicles | Customers, suppliers, customer/supplier vehicles; vehicle master, make/type/category/model; vehicle ownership and party relationships. |
| People | Employees and employee detail/maintenance. |
| Products and stock structure | Items, categories, brands; warehouses and locations; inventory balances, stock card, movements/adjustments and available stock as exposed in the inventory workspace. |
| Selling | Selling workspace, sale creation/list/detail, sales returns; downstream invoice and stock effects. |
| Purchase | Fast purchase; purchase orders; goods receipts; purchase invoices; supplier payments; purchase returns, manual supplier returns and debit notes. |
| Invoice and collections | Invoice list/detail, print/PDF/shared links; payments, payment methods and lines; cheque templates and cheque print; outstanding balances. |
| Expense and vouchers | Expense entry/types, consumable usage and report; voucher list/detail. |
| Finance | Chart of accounts, journals, ledger, trial balance, account balances, posting profiles, reversals, bank reconciliation, budgets and finance reports. |
| Tax | Tax rates/groups, customer and supplier tax profiles, tax posting profiles and reports. |
| Vehicle service | Job list **board and list**, create/edit/detail, job status and workforce/parts, service invoice and payment, offers/card promotions; recent changes added job-board filters and sales charts. |
| Promotions | Offers, card designs/templates and issued cards; assignment, eligibility and redemption must be checked through service job/invoice flows where enabled. |
| Vehicle rental | Reservations; customer and owner agreements; fleet availability and vehicle use/dispatch; running charts and trip report; expenses, incidents, vehicle finance; deposit and billing panels; rental dashboard and reports. Confirm navigation/entitlement for each workflow. |
| Reporting/dashboard | Business dashboard; customer outstanding, summary, payment-method summary, expense/consumables, purchase detail/GRN payables/receipt summary, service detail/incentives/technician work/commissions/history/sales summary, inventory aging/stock balance; rental fleet utilization, licenses, revenue, receivables, owner disbursements, profitability and incidents. |
| Audit and integration | Tenant/platform audit, immutable financial/source history, exports/print/PDF and signed shared documents; links among modules. |

## 2. Deployment smoke pack

Use one tenant admin, one restricted user, one known customer, supplier, item, vehicle, warehouse, payment method and organization unit. Do not create financial transactions in production unless the deployment's test policy explicitly permits them; use a staging tenant for write cases.

| ID | Scenario | Expected result |
|---|---|---|
| S01 | Open login, authenticate, refresh, logout, then use browser Back. | Correct tenant workspace; session survives refresh; logout denies protected page. |
| S02 | Login with a wrong password and as a disabled/revoked user. | Clear rejection, no authenticated session or data exposure. |
| S03 | Open dashboard and change date range. | All widgets render, API response matches frontend contract, totals/charts show valid populated or empty state; no console crash. |
| S04 | Switch organization unit and inspect a scoped list/report. | Visible records/totals change to the selected scope; no leakage from another unit. |
| S05 | Open main navigation entries relevant to enabled features, including direct URL refresh. | Screen loads or gives intentional permission/entitlement message, never a blank page. |
| S06 | Search and open customer, supplier, item, vehicle and employee. | Lists, search and detail work; relationship labels are readable, no raw IDs shown. |
| S07 | Open warehouse, inventory balance and stock card for a known item. | Quantity and movement history agree; filters work. |
| S08 | Create a purchase order and receive one line in staging. | PO/GRN links, stock increase, payable/accounting effects and audit appear once. |
| S09 | Create a sale or service job invoice in staging. | Correct line/tax/discount totals, invoice/PDF, stock effect and posting. |
| S10 | Record a partial customer receipt, then inspect invoice. | Payment saved once; outstanding balance decreases by exactly the receipt amount. |
| S11 | Open service jobs in board and list; search and filter by date/status. | Same underlying jobs, accurate column totals, detail navigation works. |
| S12 | Open service sales summary and dashboard sales charts. | Charts and report load for populated and empty ranges; filters agree with figures. |
| S13 | Open rental agreements, availability and running chart/report with known data. | Agreement and vehicle context is correct; reports render without errors. |
| S14 | Open ledger and trial balance for transaction date. | Source postings appear once; debit total equals credit total. |
| S15 | Open an invoice print/PDF and a permitted signed/shared link. | Correct document and tenant, readable output, invalid/expired signature rejected. |
| S16 | Restricted user tries direct create/edit/API access. | UI and backend enforce permission with no write. |
| S17 | Check an invalid required field and double-click/retry one safe staging write. | Validation is clear; no duplicate source document, movement, payment or posting. |
| S18 | Review tenant audit for a smoke transaction and refresh reports. | Event/source reference exists; dashboard/report values reconcile after refresh. |

## 3. Full regression matrix

For each row, include create/read/update where the workflow allows, search/filter/pagination, validation boundaries, status transitions, permissions, organization scope, concurrency, retry, audit and downstream effects. Historical posted records should be corrected/reversed through supported actions, not silently overwritten.

| ID | Feature | Key scenarios and assertions |
|---|---|---|
| R01 | Authentication/access | Valid/invalid login, logout, expiry, revoked session, role changes, direct URL/API denial, tenant isolation, platform-versus-tenant access, feature entitlement changes. |
| R02 | Organization/config/reference | Unit switch; tenant/unit configuration precedence and history; active/inactive lookups; duplicate codes; UOM conversion round trip; numbering uniqueness under concurrent creation. |
| R03 | Master data | Customer/supplier/employee/vehicle/item/brand/category/warehouse/location CRUD; duplicate and required-field checks; inactive entities on new forms versus historical documents; guided relationship selection. |
| R04 | Inventory | Opening/adjustment/transfer or other exposed movements; balance by warehouse/location; reservations/allocations; negative-stock rule; stock card chronology; valuation and reversal; simultaneous stock-consuming transactions. |
| R05 | Purchase order to receipt | Draft/edit/approval or available state changes, partial/multiple receipt, excess/duplicate receipt rejection, item/UOM/tax/discount totals, supplier and warehouse scope, stock and GRNI posting. |
| R06 | Purchase invoice/payments/returns | Invoice matching, partial/full supplier payment, overpayment rejection, return quantities and debit note, manual supplier return, payable and stock restoration, source links and reversal. |
| R07 | Selling and sales returns | Sale creation with valid price/tax/discount, stock deduction, partial/full return, duplicate return prevention, invoice and finance reconciliation. |
| R08 | Invoice lifecycle | Draft/post/cancel/void/reverse as supported; immutable original amounts; tax snapshot, outstanding balance, PDF/print/share, invalid signature, cross-tenant access and restoration of source effects. |
| R09 | Payments | Cash/bank/cheque methods, multiple payment lines, partial settlement, advance/allocation/refund/reversal as exposed, cheque template/print; duplicate callback/retry and no over-allocation. |
| R10 | Finance | Accounts and posting profiles, balanced journal validation, posting period boundaries, ledger/trial balance/account balances, reversal exactly once, bank reconciliation, budget and report filters. |
| R11 | Tax/expense/voucher | Tax rate/group/profile effective dates and calculations; tax reports against invoices; expense and consumable usage posting; voucher source and amount accuracy. |
| R12 | Service jobs | Create job for customer/vehicle, inspection/workforce/parts, all permitted status moves and cancellation, board/list parity, filters, pagination, concurrent edit conflict and no duplicate parts consumption. |
| R13 | Service billing | Job estimate versus final invoice, discount/tax/commission, partial payment, invoice reversal/stock restoration; sales summary, history, technician and commission reports reconcile. |
| R14 | Promotions/cards | Offer dates/eligibility, assignment, card design/issue/redeem, one-time and repeated redemption rules, invalid/expired offer, discount math and audit; test around local/UTC day boundary. |
| R15 | Rental booking/agreement | Reservation lifecycle, overlapping availability, customer and owner agreement create/activate/successor/close, daily/monthly rates, vehicle assignment and custody conflicts. |
| R16 | Rental operations/billing | Handover/return odometers, running chart finalization/correction, mileage and overtime, base/usage billing, deposit receipt/refund, owner disbursement, expenses/incidents/finance schedules; no duplicate invoice or charge. |
| R17 | Rental reports | Fleet utilization, license, revenue, lessee outstanding, owner disbursement, vehicle profitability, incident summary and running chart export agree with source filters and dates. |
| R18 | Dashboard/reports | Each report's empty/populated state, date/unit filters, sorting/export, source totals, chart drill-down; service sales weekly/profit/top-item charts match sales summary and stock balance. |
| R19 | Audit/security/integration | Every material write has actor/time/source and original values; wrong-tenant/role access denied; attachment/shared-link authorization; no secrets in UI/logs; API errors clear and atomic. |
| R20 | UI resilience | Mobile/desktop primary flows, keyboard navigation and labels, loading/empty/error states, refresh/deep link, human-readable relationships and guided selectors, no console errors. |

## 4. Highest-value end-to-end journeys

1. **Procure to pay:** supplier → PO → partial GRN → stock/GRNI → purchase invoice → partial payment → return/debit note → payable, ledger, voucher, audit and reports.
2. **Sell to collect:** item/stock → sale → invoice → partial and final receipts → return/reversal → stock, receivable, tax, ledger and reports.
3. **Service to collect:** customer + vehicle → job → workforce and parts → promotion/card → invoice → payment → commission, inventory, profit reports and audit.
4. **Rental lifecycle:** reservation → availability → customer/owner agreements → handover → running chart → base/usage invoice → deposit/payment → return → owner settlement and rental reports.
5. **Isolation and concurrency:** repeat a financial journey in two organization units and two tenants; test two users changing the same record and retrying the same action. Verify no lost update, duplicate document or cross-scope leakage.

For exact sample amounts and a connected reconciliation dataset, use [the existing full-system sample data flow](2026-09-15-full-system-sample-data-flow.md). Its fixed date and test tax are sample values, not current statutory rules. Review it against the current branch before treating every step as executable.

## 5. Exit criteria

- Smoke S01–S18 pass for applicable enabled features; any blocked case has a named owner and reason.
- Affected regression rows pass; source document, inventory, receivable/payable, ledger, dashboard/report and audit totals reconcile.
- No unauthorized or cross-tenant access; invalid and conflicting writes create no partial state.
- Record gaps between this source inventory and the deployed build as separate defects or scope decisions. Do not silently mark unverified features as passed.
