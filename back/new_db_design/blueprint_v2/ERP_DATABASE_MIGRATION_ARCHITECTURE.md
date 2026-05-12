# ERP Database Migration Architecture

## Goal

This document defines how the new ERP schema should be built in Laravel.
It assumes a fresh canonical schema and avoids reusing the current fragmented migration structure.

## Core Rule

Use one canonical migration stream only.
Do not keep parallel schema experiments active in production code.

## Recommended Migration Phases

### Phase 0001: Platform
Files:
- create_tenants_table
- create_tenant_domains_table
- create_tenant_settings_table
- create_id_sequences_table

Reason:
- all tenant-owned modules depend on tenant existence

### Phase 0002: Identity And Access
Files:
- create_users_table
- create_roles_table
- create_permissions_table
- create_user_roles_table
- create_role_permissions_table

Reason:
- user references appear across audit, posting, workflow, and operations

### Phase 0003: Organization
Files:
- create_org_units_table
- create_org_unit_closure_table
- create_user_org_units_table

Reason:
- organization scope is needed before warehouse, finance, and document ownership

### Phase 0004: Shared Party Master
Files:
- create_parties_table
- create_party_roles_table
- create_addresses_table
- create_party_addresses_table
- create_party_contacts_table
- create_tax_registrations_table

Reason:
- customer, supplier, employee, and contact relationships should all point here

### Phase 0005: Reference And Product Catalog
Files:
- create_currencies_table
- create_exchange_rates_table
- create_uoms_table
- create_uom_conversions_table
- create_product_categories_table
- create_products_table
- create_product_variants_table
- create_product_identifiers_table
- create_price_lists_table
- create_price_list_items_table

### Phase 0006: Warehouse And Inventory
Files:
- create_warehouses_table
- create_warehouse_locations_table
- create_warehouse_location_closure_table
- create_inventory_lots_table
- create_inventory_serials_table
- create_inventory_balances_table
- create_inventory_adjustment_reasons_table
- create_stock_movements_table
- create_stock_movement_lines_table
- create_inventory_layers_table
- create_inventory_layer_consumptions_table
- create_stock_reservations_table
- create_stock_count_sessions_table
- create_stock_count_lines_table

### Phase 0007: Commercial Documents
Files:
- create_document_types_table
- create_commercial_documents_table
- create_commercial_document_lines_table
- create_commercial_document_taxes_table
- create_document_links_table
- create_document_status_history_table

### Phase 0008: Finance And Subledger
Files:
- create_accounts_table
- create_fiscal_years_table
- create_fiscal_periods_table
- create_journal_entries_table
- create_journal_lines_table
- create_subledger_documents_table
- create_subledger_allocations_table
- create_bank_accounts_table
- create_bank_transactions_table
- create_bank_reconciliations_table
- create_payments_table
- create_payment_allocations_table

### Phase 0009: Audit And Integration
Files:
- create_attachments_table
- create_audit_logs_table
- create_integration_outbox_table
- create_integration_inbox_table

### Phase 0010: Seed Data And Constraints Validation
Files:
- seed_document_types
- seed_default_permissions
- seed_default_uoms
- seed_default_adjustment_reasons
- validate_required_indexes_and_constraints

## Recommended Folder Strategy

Keep schema ownership explicit but avoid duplicated table creation.

Recommended approach:
- app/Modules/Tenant/database/migrations
- app/Modules/Identity/database/migrations
- app/Modules/Organization/database/migrations
- app/Modules/Party/database/migrations
- app/Modules/Product/database/migrations
- app/Modules/Warehouse/database/migrations
- app/Modules/Commercial/database/migrations
- app/Modules/Finance/database/migrations
- app/Modules/Audit/database/migrations

Important:
- each table exists in exactly one module
- other modules reference it, but never redefine it

## Migration Naming Rules

Use clear, stable names:
- create_parties_table
- create_commercial_documents_table
- create_journal_lines_table

Avoid:
- vague table names
- duplicate semantic names
- version-like alternate tables for the same business concept

## ID Strategy

Recommended default:
- bigint unsigned primary keys for OLTP performance and simple FK handling

Optional alternative:
- uuid for external APIs only, stored as separate public_id column

Reason:
- bigint remains simpler and faster for joins, indexing, and Laravel relationships
- public_id can be exposed safely without leaking internal row counts

## FK Strategy

### Strong FK required
Use real foreign keys for:
- tenant ownership
- master data references
- document headers to lines
- ledger headers to lines
- warehouse and location references
- product variant references
- user references

### Controlled weak reference allowed only at integration boundaries
Allowed only when necessary for generic linkage:
- attachments attachable_type and attachable_id
- audit_logs auditable_type and auditable_id
- integration_outbox aggregate_type and aggregate_id

Reason:
- these are boundary patterns, not core transactional joins

## Transaction Safety Rules

### Rule 1
Header and line inserts must occur in one DB transaction.

### Rule 2
Inventory movement, layer consumption, balance update, and journal generation must commit atomically.

### Rule 3
Never publish external messages in the same transaction by direct network call.
Write to integration_outbox instead.

### Rule 4
Posted financial entries are immutable.
Corrections must happen through reversal or adjustment entries.

### Rule 5
Stock history is immutable.
Corrections must happen through compensating movements, not destructive updates.

## Status Handling Rules

Use application-managed state transitions.

Recommended implementation:
- status_code string column
- optional status metadata config per aggregate type
- immutable status history table for document lifecycle

Example document lifecycle:
- draft
- confirmed
- posted
- fulfilled
- closed
- cancelled

Do not allow arbitrary string states from controllers.

## Totals And Derived Values

### Store on headers for speed
Allowed stored values:
- subtotal_amount
- discount_amount
- tax_amount
- grand_total_amount
- cost_total_amount

### Source of truth remains lines
Header totals must always be recomputed from lines in domain services.
Do not trust manual updates to header totals.

## Nullability Rules

Use nullable only when the relation is genuinely optional.

Good nullable examples:
- cancelled_at
- posted_at before posting
- ship_to_party_id when not applicable
- lot_id for non-lot-tracked items
- serial_id for non-serial-tracked items

Bad nullable examples:
- tenant_id
- document_type_id
- product_variant_id on stock movement line
- account_id on journal line
- warehouse_id when the domain requires physical stock ownership

## Index Validation As Part Of Build

Add a schema validation test suite that checks:
- all required FKs exist
- all unique business keys exist
- all high-volume tables have tenant-first indexes
- no duplicated table names exist in active migration paths

## Recommended Tests

### Schema tests
- migration succeeds from empty database
- migration rollback succeeds for local/dev flows
- all mandatory indexes exist
- all FK constraints exist

### Domain integrity tests
- document posting creates expected stock and journal records
- payment allocation updates subledger correctly
- stock reservation expiry releases inventory correctly
- reversing a posted journal creates valid compensating entry

## Suggested Future Implementation Sequence

1. Create blueprint migrations in a new canonical branch of the schema
2. Build shared reference tables first
3. Implement unified commercial document engine
4. Implement inventory ledger and balance snapshot model
5. Implement finance and subledger bridge
6. Add archive jobs and partition strategy
7. Build reporting marts after OLTP model stabilizes

## What Must Not Return In The New Design

Do not reintroduce:
- duplicated customer and supplier master tables as separate primary identities
- duplicated purchase and sales document families for identical behavior
- free-form status columns without lifecycle governance
- high-volume soft-deleted history tables
- multiple active schema tracks for the same entities
- unconstrained foreign keys in core transactional tables

## Final Recommendation

The new ERP schema should be treated as infrastructure, not as a collection of per-module table experiments. One canonical migration path, one shared master model, and one unified document engine will give the project the best long-term result for performance, maintainability, and growth.