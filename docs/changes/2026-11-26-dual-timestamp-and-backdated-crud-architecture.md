# 2026-11-26 — Universal Dual-Timestamp & Backdated CRUD Architecture Codification

## Context & Objectives
- Researched and evaluated the architectural approach for implementing backdate support across all CRUD operations throughout the AutoERP project.
- Thoroughly analyzed business requirements, GAAP/IFRS accounting compliance, data integrity, operational dependencies, validation rules, audit history, and edge cases across all core functional domains:
  1. Finance & General Ledger (running balance projections, accounting periods, journal reversals, point-in-time account balances).
  2. Inventory Management (multi-period intermediate negative stock check, valuation layers FIFO/LIFO/WAC, backdated physical stock adjustments, chronological UOM revisions).
  3. Invoicing & Debtors/Creditors (tax document mapping, reversal date policy, dynamic point-in-time aging reconstruction).
  4. Payments, Cash Receipts & Realized FX (cash receipt fraud guardrails, multi-currency realized FX gain/loss on backdated allocations, reversal propagation).
  5. Purchasing & Procurement (temporal causality chain across PO, GRN, Purchase Return, and Debit Note).
  6. Selling & POS (operational date propagation across inventory, pricing, tax, invoices, and ledger).
  7. Document Sequence Partitioning (temporal sequence bucket partitioning based on civil date `{PERIOD}`).
  8. Human Resources, Attendance & Biometrics (hardware punch time vs server ingestion timestamp, finalized attendance period locks, attendance regularizations).
  9. Payroll & Retroactive Pay (immutable historical payslips, retroactive pay arrears engine).
  10. Vehicle Rental & Fleet Management (monotonic odometer validation, temporal custody handover/return).
  11. Vehicle Service Workshop (job date propagation, promotion qualification temporal parameters).
  12. Unified Voucher Presentation Ledger (multi-source civil date aggregation).
  13. Audit Subsystem (dual timestamp schema design separating `occurred_at` from `recorded_at`).
- Codified the universal backdated CRUD behavioral matrix, master data snapshotting vs effective-dating patterns, blueprint for future Fixed Assets module, and canonical model lifecycle immutability patterns into permanent project architecture documentation.

## Modified & Created Files Summary
| File | Action | Description |
|---|---|---|
| `docs/architecture/dual-timestamp-and-backdated-crud-architecture.md` | Created | Authoritative technical specification for dual-timestamp separation, universal backdated CRUD matrix, subsystem invariants, master data patterns, GAAP/IFRS compliance, and developer checklist. |
| `docs/README.md` | Modified | Added the canonical dual-timestamp architecture document to the documentation catalog and architecture index. |
| `docs/changes/2026-11-26-dual-timestamp-and-backdated-crud-architecture.md` | Created | Append-only change record detailing architecture research, codification, and test verification evidence. |

## Verification & Quality Gate Results
- **Focused Temporal & Backdated Automated Test Gates:**
  - Command: `php artisan test --filter "BackdatedCrudSafetyTest|FinanceEngineTest|StockCardTest"`
  - Results:
    - `Tests\Feature\Core\BackdatedCrudSafetyTest`: 13 passed (tax mapping, reversal before posted_at, FIFO layer ordering, running balance projections, intermediate negative stock prevention, reversed movement stability, future date rejection, historical stock validation, point-in-time aging, and model immutability hooks).
    - `Modules\Finance\Tests\FinanceEngineTest`: 9 passed (ledger balance projections, foreign currency journals, balanced reversals, and backdated posting projections).
    - `Modules\Inventory\Tests\StockCardTest`: 7 passed (point-in-time stock cards, reversals, opening balances, workspace timezone resolution, and cascading UOM revisions).
    - **Total: 29 passed (169 assertions, 0 failures, duration 6.21s)**.

## Author & Maintainer
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [AutoERP](https://github.com/kasunvimarshana/AutoERP)
