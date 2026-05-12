# ERP Database Index And Retention Guide

## Indexing Strategy

The new schema uses a small set of repeatable rules.

### Rule 1: Tenant-first indexing

Every tenant-owned transactional table gets an index that starts with tenant_id.

Why:

- tenant isolation is the most common filter
- it improves row locality and predictable query plans
- it reduces cross-tenant scan risk in multi-tenant workloads

### Rule 2: Every FK must be index-backed

Foreign keys should always have an index unless they are already the leading part of a composite index.

### Rule 3: Uniques should reflect business identity

Examples:

- tenants: unique tenant_code
- users: unique tenant_id + email
- products: unique tenant_id + product_code
- product_variants: unique tenant_id + sku
- commercial_documents: unique tenant_id + document_number
- stock_movements: unique tenant_id + movement_number
- journal_entries: unique tenant_id + entry_number
- payments: unique tenant_id + payment_number

### Rule 4: Snapshot tables use dense composite indexes

inventory_balances must support stock lookup by the exact physical grain.

Recommended unique index:

- tenant_id, warehouse_id, location_id, product_variant_id, lot_id, serial_id, unit_of_measure_id

### Rule 5: Large append-only tables should favor write-safe indexes

Do not over-index audit_logs, journal_lines, or stock_movement_lines.
Use only the indexes that support actual operational and reporting needs.

## Critical Indexes By Table

### users

- unique: tenant_id + email
- index: tenant_id + status_code
- index: tenant_id + party_id

### parties

- unique: tenant_id + party_code
- index: tenant_id + legal_name
- index: tenant_id + status_code

### products

- unique: tenant_id + product_code
- index: tenant_id + product_category_id
- index: tenant_id + product_kind

### product_variants

- unique: tenant_id + sku
- index: tenant_id + product_id
- index: tenant_id + status_code

### warehouses

- unique: tenant_id + warehouse_code
- index: tenant_id + org_unit_id
- index: tenant_id + is_active

### warehouse_locations

- unique: tenant_id + warehouse_id + location_code
- index: tenant_id + warehouse_id + parent_id
- index: tenant_id + warehouse_id + location_type

### inventory_lots

- unique: tenant_id + lot_code
- index: tenant_id + product_variant_id
- index: tenant_id + expiry_date
- index: tenant_id + warehouse_id + status_code

### inventory_serials

- unique: tenant_id + serial_code
- index: tenant_id + product_variant_id
- index: tenant_id + warehouse_id + status_code

### inventory_balances

- unique: tenant_id + warehouse_id + location_id + product_variant_id + lot_id + serial_id + unit_of_measure_id
- index: tenant_id + product_variant_id
- index: tenant_id + warehouse_id
- index: tenant_id + location_id

### stock_movements

- unique: tenant_id + movement_number
- index: tenant_id + movement_type + movement_at
- index: tenant_id + warehouse_id + movement_at
- index: tenant_id + status_code + movement_at

### stock_movement_lines

- index: tenant_id + stock_movement_id + product_variant_id
- index: tenant_id + product_variant_id + created_at
- index: tenant_id + source_location_id
- index: tenant_id + destination_location_id

### inventory_layers

- index: tenant_id + product_variant_id + warehouse_id + received_at
- index: tenant_id + lot_id
- index: tenant_id + serial_id
- index: tenant_id + qty_remaining

### stock_reservations

- index: tenant_id + source_type + source_id
- index: tenant_id + product_variant_id + warehouse_id
- index: tenant_id + status_code + expires_at

### stock_count_sessions

- unique: tenant_id + count_number
- index: tenant_id + warehouse_id + status_code
- index: tenant_id + counted_at

### commercial_documents

- unique: tenant_id + document_number
- index: tenant_id + document_type_id + status_code + document_date
- index: tenant_id + party_id + document_date
- index: tenant_id + warehouse_id + document_date
- index: tenant_id + due_date + status_code

### commercial_document_lines

- unique: tenant_id + commercial_document_id + line_no
- index: tenant_id + product_variant_id
- index: tenant_id + warehouse_id + location_id
- index: tenant_id + source_document_line_id

### document_links

- index: tenant_id + source_document_id + link_type
- index: tenant_id + target_document_id + link_type

### document_status_history

- index: tenant_id + commercial_document_id + changed_at
- index: tenant_id + to_status_code + changed_at

### accounts

- unique: tenant_id + account_code
- index: tenant_id + parent_id
- index: tenant_id + account_type
- index: tenant_id + is_active

### fiscal_periods

- unique: tenant_id + fiscal_year_id + period_number
- index: tenant_id + status_code + start_date

### journal_entries

- unique: tenant_id + entry_number
- index: tenant_id + fiscal_period_id + status_code + posting_date
- index: tenant_id + reference_type + reference_id
- index: tenant_id + entry_date

### journal_lines

- index: tenant_id + journal_entry_id + line_no
- index: tenant_id + account_id + journal_entry_id
- index: tenant_id + party_id
- index: tenant_id + org_unit_id

### subledger_documents

- unique: tenant_id + subledger_type + document_number
- index: tenant_id + party_id + status_code + due_date
- index: tenant_id + source_document_id
- index: tenant_id + document_date

### subledger_allocations

- index: tenant_id + subledger_document_id
- index: tenant_id + reference_type + reference_id
- index: tenant_id + allocated_at

### payments

- unique: tenant_id + payment_number
- unique: tenant_id + idempotency_key
- index: tenant_id + party_id + payment_date
- index: tenant_id + status_code + payment_date
- index: tenant_id + bank_account_id + payment_date

### payment_allocations

- index: tenant_id + payment_id
- index: tenant_id + commercial_document_id
- index: tenant_id + subledger_document_id
- index: tenant_id + allocated_at

### bank_accounts

- unique: tenant_id + account_number_masked
- index: tenant_id + account_id
- index: tenant_id + status_code

### bank_transactions

- unique: tenant_id + bank_account_id + external_reference
- index: tenant_id + bank_account_id + transaction_date
- index: tenant_id + reconciliation_status_code + transaction_date
- index: tenant_id + value_date

### bank_reconciliations

- index: tenant_id + bank_account_id + period_end
- index: tenant_id + status_code

### attachments

- index: tenant_id + attachable_type + attachable_id
- index: tenant_id + path
- index: tenant_id + uploaded_by

### audit_logs

- index: tenant_id + occurred_at
- index: tenant_id + action_code + occurred_at
- index: tenant_id + auditable_type + auditable_id
- index: tenant_id + user_id + occurred_at

### integration_outbox

- index: status_code + available_at
- index: tenant_id + aggregate_type + aggregate_id
- index: tenant_id + processed_at

### integration_inbox

- unique: source_system + message_key
- index: tenant_id + received_at
- index: tenant_id + status_code + received_at

## Large Table Retention Strategy

### Tables that grow fastest

1. audit_logs
2. stock_movement_lines
3. journal_lines
4. commercial_document_lines
5. inventory_layer_consumptions
6. document_status_history
7. bank_transactions
8. integration_outbox
9. integration_inbox

### Partition-ready tables

Recommended first candidates for partitioning by month or quarter:

- audit_logs
- stock_movement_lines
- journal_lines
- bank_transactions
- integration_outbox
- integration_inbox

## Archive Policy Matrix

### Permanent master tables

Retention: permanent

Tables:

- tenants
- users
- roles
- permissions
- org_units
- parties
- products
- product_variants
- accounts
- fiscal_years
- fiscal_periods

### Warm archive after business inactivity

Retention:

- keep hot for 24 to 36 months
- archive after that

Tables:

- commercial_documents
- commercial_document_lines
- subledger_documents
- payments
- bank_reconciliations

### Financial and inventory history

Retention:

- keep hot for 24 months
- archive by fiscal period or movement month

Tables:

- stock_movements
- stock_movement_lines
- inventory_layers
- inventory_layer_consumptions
- journal_entries
- journal_lines

### Aggressive cleanup tables

Retention:

- audit_logs: hot 6 months, warm 24 months, cold archive after that
- integration_outbox: keep processed 30 to 90 days
- integration_inbox: keep processed 90 to 180 days
- document_status_history: hot 12 months, archive after 24 months

## Soft Delete Vs Archive Rules

Use soft deletes for:

- users
- org_units
- parties
- products
- product_variants
- warehouses
- warehouse_locations
- price_lists

Do not use soft deletes for:

- stock_movements
- stock_movement_lines
- inventory_layers
- journal_entries
- journal_lines
- audit_logs
- bank_transactions
- integration_outbox
- integration_inbox

Reason:

- those records are historical facts, not recoverable business drafts

## Cleanup Jobs

### Daily

- purge processed integration_outbox beyond retention
- purge processed integration_inbox beyond retention
- clean expired stock_reservations

### Monthly

- move old audit_logs to archive storage
- archive document_status_history older than retention window
- rebuild statistics on high-write tables

### Quarterly

- archive old stock movements and journal lines by closed period
- validate checksum counts between hot and archive stores
- review index fragmentation

## Reporting Strategy

Do not overload OLTP tables with heavy analytics.

Use normalized write model plus reporting marts:

- inventory_daily_fact
- sales_daily_fact
- purchasing_daily_fact
- receivables_aging_fact
- payables_aging_fact
- gl_balance_fact

Refresh rules:

- intra-day incremental for operational dashboards
- nightly rebuild for period reporting

## Summary

This indexing and retention strategy is designed to keep the new ERP schema fast, lean, and maintainable for long-term growth. The key difference from the old design is intentional lifecycle management from day one, especially for log and ledger style tables.
