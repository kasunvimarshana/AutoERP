# Finalized expense management requirements

Date: 2026-09-10

Status: Confirmed for future development; implementation has not started

## Purpose

This document is the approved source of truth for a future Expenses module that records operating expenses, supplier liabilities, payments, inventory consumption where applicable, and their effect on monthly profit.

This record finalizes the requirement and development direction only. It does not authorize implementation by itself. Development must begin only after the user explicitly requests it.

## Business requirement

The system must provide a separate, focused Expenses workspace for entries such as:

- electricity bills;
- water bills;
- rent and internet charges;
- shampoo, cleaning liquid, and similar supplies that are not tracked as stock; and
- the cost of shampoo, cleaning liquid, or other consumables that are tracked as stock and expensed only when internally consumed.

Posted expenses must reduce profit for the correct accounting period and must remain traceable to the supplier bill, selected expense account, payment, journal, and ledger entry.

The Expenses workspace is a user-facing workflow and orchestration boundary. It must reuse the existing Invoice, Payment, Finance, Supplier, Tax, and Inventory domain services and canonical tables. It must not introduce a parallel invoice, payable, payment, journal, or ledger implementation.

## Accounting decisions

### Direct operating expenses

Electricity, water, rent, internet, and non-stock supplies are recognized when the related supplier expense invoice is posted:

```text
Dr Selected Expense Account
Dr Input Tax Receivable (when applicable)
Cr Supplier Payable
```

Paying the invoice later records:

```text
Dr Supplier Payable
Cr Cash / Bank / applicable payment clearing account
```

Payment does not create a second expense and does not reduce profit again. It changes the supplier liability and cash position only.

### Non-stock consumables

If shampoo, cleaning liquid, or a similar supply is not quantity-tracked after purchase, it may be entered as a direct expense. Its full eligible cost is recognized when the expense invoice is posted. No later inventory-consumption posting is permitted for the same cost.

### Stock-tracked consumables

If remaining quantities must be tracked, the item must be purchased as a stockable consumable through the Purchase and Inventory flows. The purchase is capitalized as Inventory and is not an operating expense at receipt:

```text
Dr Inventory
Cr GRNI / Supplier Payable through the existing purchase lifecycle
```

Only quantities actually consumed internally are expensed:

```text
Dr Selected Consumables Expense Account
Cr Inventory
```

The implementation must prevent the same cost from being expensed at purchase and again at consumption. A stockable item cannot be submitted through the direct-expense workflow.

### Items sold to customers

Items purchased for resale are not operating expenses. Their cost follows the authoritative stock issue and Cost of Goods Sold flow when sold. The Expenses module must not be used to bypass Sales, Inventory, or COGS recognition.

## Current-system baseline

At the time this requirement was finalized:

- `Purchase > Fast Purchase` contains an `Expense Only` preset for non-stock supplier costs;
- that preset creates a purchase order and supplier invoice without a GRN;
- the current UI still requires a warehouse even for an expense-only transaction;
- the current `Expense Only` preset does not expose immediate payment, although the backend supports a direct non-stock invoice-and-payment combination;
- non-stock purchase invoice amounts resolve to the generic `Purchase Expense` Finance role/account;
- manual inbound supplier invoices are supported by the Invoice module but aggregate the expense posting instead of retaining a selected expense account per line;
- supplier payments, allocations, invoice balances, journals, and ledger entries already provide the canonical payable and cash lifecycle;
- the Profit and Loss service already calculates profit from posted revenue and expense ledger accounts; and
- there is no general-purpose, financially posted internal-consumption workflow for stockable supplies outside specialized owning workflows.

## Module and ownership design

### Expenses module owns

- the Expenses navigation and focused user experience;
- expense-specific request validation and authorization;
- orchestration of expense invoice preview, creation, posting, and optional payment;
- controlled selection of eligible expense accounts;
- expense registers and monthly expense reporting; and
- links and drill-down navigation to the canonical invoice, payment, journal, and ledger records.

### Existing modules remain authoritative

- Supplier owns supplier master data and supplier eligibility.
- Invoice owns invoice headers, lines, tax calculations, balances, lifecycle, snapshots, cancellation, and reversal integration.
- Payment owns payment methods, payment lines, supplier allocations, instrument states, and payment posting.
- Finance owns the Chart of Accounts, posting-account validation, journals, ledger entries, Profit and Loss, and reversals.
- Purchase owns stock procurement, purchase orders, GRNs, purchase invoice lineage, and supplier returns.
- Inventory owns stock quantities, valuation layers, inventory movements, internal consumption, and movement reversals.

The Expenses module must call public application/domain services owned by these modules. It must never write directly into another module's tables or reproduce another module's calculations.

## Required UI and workflow

### Navigation

Add a top-level or tenant-enabled `Expenses` module with the following focused pages:

1. `Expenses Dashboard`
2. `Add Expense`
3. `Expense List`
4. `Expense Accounts`
5. `Monthly Expense Report`

The primary flow must remain short. History, analytics, journals, and advanced accounting detail belong in separate views or drill-down panels.

### Add Expense

Required or conditional inputs:

- supplier, selected through a controlled searchable selector;
- supplier bill/reference number;
- bill/invoice date;
- due date or payment terms;
- organization unit and currency context resolved from the active workspace and supplier rules;
- one or more expense lines;
- human-readable expense account per line;
- description;
- quantity and unit amount, defaulting to a simple quantity of one where appropriate;
- tax group where applicable;
- notes; and
- settlement choice: `Pay Later` or `Pay Now`.

When `Pay Now` is selected, show controlled payment-method rows and the required reference/instrument fields. Do not show payment fields for `Pay Later`.

The expense account selector must return only active, postable, in-scope Finance accounts whose account type belongs to the income statement's expense side. Raw database identifiers must never be displayed or manually entered.

Stockable items must be rejected with guidance to use Purchase and Internal Consumption. Non-stock, service, labour where legitimately used as an expense, and non-stockable consumable items may be accepted according to Item usage rules.

Warehouse and warehouse location must not be requested for a direct operating expense because no inventory movement is created.

### User actions and lifecycle

Support actions according to existing permissions and lifecycle rules:

- `Preview` shows totals, tax, payable amount, selected accounts, and documents that will be created without writing transactional data.
- `Save Draft` creates a canonical draft expense invoice.
- `Post Expense` approves/posts the expense invoice through the existing Invoice and Finance lifecycle.
- `Post & Pay` posts the invoice, creates the supplier payment, and allocates it to that invoice through the existing Payment lifecycle.

`Post & Pay` must be atomic and idempotent. A partial failure must not leave an unallocated payment, a duplicated invoice, or a posted invoice that the combined operation reported as failed. If existing lifecycle permissions require separate approval actors, the UI must respect those rules rather than bypassing them.

Posted history must not be edited in place. Corrections must use the canonical cancellation, credit, or reversal mechanisms appropriate to the document's state.

### Expense List

The list must show human-readable values and support filters for:

- date range;
- supplier;
- supplier reference;
- expense account;
- invoice status;
- payment status; and
- organization unit where permitted.

Each record must drill down to its canonical supplier invoice, payment allocations, journal, and voucher where available.

### Expense Accounts

Expense categories in the first version are Finance posting accounts, not a duplicated category master. Authorized users may manage accounts through the existing Chart of Accounts. The Expenses view provides a filtered, readable view or navigation to those accounts.

Examples include:

- Electricity Expense;
- Water Expense;
- Cleaning Supplies Expense;
- Internet Expense; and
- Rent Expense.

A separate management-category table is out of scope unless a later confirmed requirement establishes categories that are intentionally independent from the Chart of Accounts.

## Canonical data flow

### Pay Later

```text
Expenses UI
  -> Expense orchestration service
  -> Invoice module creates inbound expense invoice and lines
  -> Invoice approval/posting
  -> Finance module posts expense/tax/payable journal
  -> Supplier payable remains outstanding
```

### Pay Now

```text
Expenses UI
  -> Expense orchestration service
  -> Invoice module creates and posts inbound expense invoice
  -> Payment module creates outbound supplier payment
  -> Payment module allocates payment to the expense invoice
  -> Finance module posts both invoice and payment journals
```

The existing supplier-payment workspace must also be able to settle a posted outstanding expense invoice later.

## Required database design

Use one table per migration and explicit portable Laravel migration definitions. The exact migration filenames must follow the owning module's convention. Do not modify historical posted data to manufacture new classifications.

### `invoices`

Add a nullable supplier-facing reference field using the established naming convention, recommended as:

- `supplier_reference`

Add an index that supports supplier/reference lookup and backend duplicate detection. Duplicate validation must be tenant- and supplier-scoped and must account for cancelled/reversed lifecycle rules without relying solely on a database error message.

Add `expense` as a controlled `InvoiceType` enum value. The existing string column can store the new value; no duplicate expense header table is required.

### `invoice_lines`

Add an optional line-level posting-account snapshot using generic invoice terminology so the foundation remains reusable:

- `posting_account_id`;
- `posting_account_code_snapshot`; and
- `posting_account_name_snapshot`.

`posting_account_id` must use a tenant-safe relationship to Finance accounts and must be immutable after the invoice becomes posted. The code and name snapshots preserve the original visible classification if the Chart of Accounts is renamed later.

The backend must derive and validate the selected account. It must never trust a frontend-supplied ID without tenant, organization, active, postable, and expense-type checks.

### Existing canonical tables

No expense-specific duplicates may replace these existing stores:

- `invoices` and `invoice_lines` for the expense document;
- the existing invoice balance table for paid and outstanding amounts;
- `payments`, payment lines, and payment allocations for settlement;
- `finance_journal_entries` and `finance_journal_lines` for accounting documents; and
- existing ledger entries for financial reporting.

### Internal consumption tables

Stock-tracked supply consumption belongs to Inventory and requires its own operational documents because a supplier expense invoice cannot represent physical usage.

#### `inventory_consumptions`

Required header data:

- row version;
- tenant and organization unit;
- consumption number and date;
- warehouse;
- lifecycle status;
- notes/reason;
- created, approved, posted, cancelled, and reversed actor/timestamp fields as required by the established lifecycle; and
- reversal relationship where applicable.

#### `inventory_consumption_lines`

Required line data:

- tenant and organization unit;
- consumption header;
- line number;
- item, variant, UOM, warehouse location, batch, and serial/allocation references where applicable;
- entered and base-quantity snapshots;
- selected expense posting account and readable account snapshots;
- resolved valuation method, unit cost, and total cost snapshots;
- description; and
- source inventory movement reference after posting.

Posting an internal consumption document must lock and validate stock, consume the correct valuation layers, create an outbound inventory movement, post Expense against Inventory using the resolved valuation cost, and complete the document atomically.

## Posting-plan changes

The Invoice module's inbound manual posting plan must be extended from one aggregated generic expense line to line-level expense postings. Each direct-expense invoice line must retain its selected account and amount while tax, withholding, payable, and rounding remain reconciled to the document total.

Finance remains the only authority that resolves or validates a Finance account for posting. The posting DTO/service contract may be extended with a controlled explicit-account reference, but this must not allow arbitrary clients or other modules to bypass posting-profile rules. The Expenses orchestrator supplies business intent; Finance validates and materializes the journal account.

Existing stock purchase, GRNI, COGS, sales invoice, rental, and other posting profiles must retain their current behavior.

## Reporting requirements

### Monthly Expense Report

Provide a ledger-reconcilable report with filters for:

- date range/month;
- expense account;
- supplier;
- item/description where applicable;
- invoice status;
- payment status; and
- organization unit where permitted.

Required information includes:

- posting date;
- supplier and supplier reference;
- expense invoice number;
- expense account;
- net amount, input tax, and gross amount;
- paid and outstanding amounts;
- payment date/reference when paid;
- source type (`direct expense` or `inventory consumption` where included); and
- links to the canonical documents and journal.

Only posted, non-reversed financial activity contributes to financial totals. Draft and cancelled records may be displayed operationally but must not be mixed into posted expense totals.

### Profit and Loss

The existing ledger-backed Profit and Loss calculation remains authoritative:

```text
Net Profit = Posted Revenue - Posted COGS - Posted Operating Expenses
```

No separate Expenses-module profit calculation may be created. Direct expenses enter Profit and Loss on the invoice posting date. Stockable consumables enter Profit and Loss on the internal-consumption posting date. Supplier payment dates do not change profit.

### Inventory Consumption Report

When stock-tracked consumption is implemented, provide an Inventory-owned report containing item, quantity, valuation cost, warehouse/location, expense account, consumption date, status, movement, journal, and reversal information.

## Validation and integrity requirements

- Every write must be tenant- and organization-scoped.
- All combined writes must run in database transactions with row locks where shared balances, invoice states, payment allocations, or inventory quantities can change concurrently.
- Mutation endpoints must use row versions or equivalent conflict detection.
- Create/post/pay operations must use idempotency keys to prevent duplicates on retries.
- Supplier references must be checked for likely duplicate bills within the same tenant and supplier.
- Payment allocations cannot exceed the posted invoice balance.
- Currency and exchange-rate rules must use the existing Invoice and Payment authorities.
- Expense account selections must be active, postable, expense-type, and valid for the posting scope/date.
- Stockable items must never enter the direct-expense flow.
- Non-stock direct expenses must never create GRNs or inventory movements.
- Internal consumption cannot exceed available stock and must use authoritative inventory valuation rather than a user-entered cost.
- Posted invoices, payments, journals, movements, and consumption records are immutable; corrections require append-only cancellation/reversal records.
- APIs must return structured supplier, account, item, payment, and document objects instead of isolated raw foreign keys.
- Error handling must be explicit; failures must not be silently ignored or converted into partial success.

## Permissions

Introduce explicit Expenses permissions following the established permission catalogue convention:

- `expenses.view`;
- `expenses.create`;
- `expenses.approve` where approval is enabled;
- `expenses.post`;
- `expenses.pay`;
- `expenses.cancel` or `expenses.reverse` according to lifecycle; and
- `expenses.reports.view`.

These permissions orchestrate actions but do not override mandatory Invoice, Payment, Finance, Supplier, or Inventory authorization boundaries. The final permission composition must avoid requiring users to understand or receive broad raw Finance-maintenance access merely to enter a controlled expense.

## Delivery sequence

1. Add expense invoice type/reference and line-level posting-account foundation.
2. Extend Finance-safe line-level expense account resolution and invoice posting plans.
3. Add Expenses permissions, API orchestration, preview, idempotency, and tests.
4. Build the Expenses navigation, Add Expense, list, account view, and document drill-down UI.
5. Add Pay Now and Pay Later integration using canonical Payment services.
6. Add the ledger-reconcilable Monthly Expense Report and verify Profit and Loss behavior.
7. Add Inventory-owned Internal Consumption documents, postings, UI, reversals, and report for stock-tracked supplies.
8. Run backend, frontend, concurrency, permission, accounting, reversal, and regression verification before completion.

Implementation may be delivered in tested vertical slices, but no slice may introduce a temporary duplicate ledger or bypass another module's ownership.

## Acceptance criteria

- A user can enter an electricity or water bill from the Expenses module without selecting a warehouse.
- Each expense line can post to a different controlled expense account.
- A posted expense creates the correct supplier payable and reduces Profit and Loss exactly once.
- `Pay Later` leaves the correct outstanding supplier balance and can be paid through the existing Supplier Payments flow.
- `Pay Now` creates and allocates the canonical supplier payment without duplicating the expense.
- Expense invoices, payments, vouchers, journals, and ledger entries are mutually traceable.
- The monthly report reconciles to posted expense journal lines and Profit and Loss totals.
- A non-stock supply can be expensed directly without an inventory movement.
- A stockable consumable cannot be direct-expensed and is expensed only through posted internal consumption.
- Internal consumption reduces stock and recognizes only the valuation cost of the quantity actually consumed.
- Concurrent submissions, retries, and reversals do not duplicate documents, over-allocate payments, over-issue stock, or corrupt balances.
- Existing Purchase, supplier invoice, supplier payment, Inventory, Finance, tax, and reporting flows continue to work without regression.

## Explicitly excluded from this finalized scope

- a second expense ledger or a second supplier-payable balance;
- direct manual debit/credit entry in the normal Expenses UI;
- treating stock purchased for resale as an operating expense;
- expensing the same stockable supply at both purchase and consumption;
- silently editing posted expense history;
- uncontrolled free-text account/category values;
- recurring auto-generated expenses, budgets, employee reimbursement/claim workflows, OCR, and approval-policy redesign unless separately confirmed later; and
- development work before the user gives a separate explicit instruction to begin implementation.

## Changes in this record

- Added this finalized requirements document only.
- No application behavior, source code, database schema, permission, transactional record, or report was changed.
