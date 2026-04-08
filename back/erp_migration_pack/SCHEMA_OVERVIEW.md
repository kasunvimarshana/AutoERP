# ERP Migration Pack Overview

This pack contains module-based Laravel migrations under `app/Modules/<Module>/database/migrations`.

Design highlights:
- Multi-tenant isolation via `tenant_id`.
- Recursive hierarchies via self-references and closure tables.
- Unified trade documents for purchase/sales/direct/returns using `commercial_documents`.
- Double-entry accounting via `chart_accounts`, `journal_entries`, `journal_lines`.
- Period-based accrual accounting via `fiscal_years` and `fiscal_periods`.
- AP/AR open items via `subledger_documents` and `subledger_allocations`.
- Inventory traceability via `inventory_layers`, `stock_movements`, `stock_movement_lines`, plus AIDC and traceability logs.
- Polymorphic attachments and identifiers.
