# ETL Mapping Strategy: Legacy to Canonical Schema

**Version:** 1.0  
**Date:** 2026-05-10  
**Status:** Ready for Implementation  
**Phase:** Data Migration Planning

---

## 1. Executive Summary

This document provides a comprehensive mapping strategy for migrating data from the current fragmented, multi-track ERP database schema to the new unified canonical schema.

### Key Architectural Changes

| Aspect | Legacy State | Canonical State |
|--------|-------------|-----------------|
| **Commercial Documents** | 12 separate tables (PO, GRN, Receipt, Invoice, Return × Purchase/Sales/Service) | 1 unified `commercial_documents` table with `document_type_id` |
| **Inventory Model** | Separate `batches`, `serials`, `warehouse_locations` | Unified `inventory_lots`, `inventory_serials` with location closure tree |
| **Master Data** | Separate `suppliers`, `customers`, `employees` tables | Unified `parties` table with `party_roles` (supplier, customer, employee) |
| **Product Variants** | Free-form SKU on products | Structured `product_variants` table with unique SKU per tenant |
| **Finance Posting** | Inconsistent GL account references | Strict subledger-to-GL bridge via `subledger_documents` and `subledger_allocations` |
| **Status Tracking** | Free-form string values (risk of invalid transitions) | Typed enums + immutable `document_status_history` audit trail |
| **Audit Trail** | Limited/no history; soft deletes on transactional tables | Immutable `audit_logs` + append-only `integration_outbox` |

---

## 2. Migration Philosophy

### Core Principles

1. **Data Fidelity**: Every transactional record in the legacy schema is preserved; no data loss
2. **Referential Integrity**: All foreign key relationships are validated and corrected during migration
3. **Audit Compliance**: All migrated records include source reference (legacy table/ID) in metadata
4. **Reversibility**: Migration is testable; rollback procedures exist for each phase
5. **Minimal Downtime**: Open transactions are migrated with dual-write validation before cutover

### High-Level Migration Sequence

```
Phase 1: Foundation Layer
├─ tenants → tenants (direct copy, no change)
├─ users → users (migrate with new role structure)
├─ roles → roles (map old roles to new RBAC model)
└─ org_units → org_units + org_unit_closure

Phase 2: Master Data Layer
├─ suppliers + customers + employees → parties + party_roles
├─ products + variants → products + product_variants
├─ categories → product_categories
└─ uom mappings → uoms + uom_conversions

Phase 3: Inventory Layer
├─ warehouses → warehouses (direct)
├─ warehouse_locations → warehouse_locations + warehouse_location_closure
├─ batches + serials → inventory_lots + inventory_serials
├─ stock_movements (current quantities) → inventory_balances (snapshot) + stock_movements (historical)
└─ inventory_layers initialization (from current FIFO/WEIGHTED values)

Phase 4: Commercial Documents Layer
├─ purchase_orders + purchase_order_lines → commercial_documents + commercial_document_lines (document_type='PurchaseOrder')
├─ grn_headers + grn_lines → commercial_documents + commercial_document_lines (document_type='PurchaseReceipt')
├─ purchase_invoices + purchase_invoice_lines → commercial_documents + commercial_document_lines (document_type='PurchaseInvoice')
├─ purchase_returns + purchase_return_lines → commercial_documents + commercial_document_lines (document_type='PurchaseReturn')
├─ sales_orders + sales_order_lines → commercial_documents + commercial_document_lines (document_type='SalesOrder')
├─ sales_invoices + sales_invoice_lines → commercial_documents + commercial_document_lines (document_type='SalesInvoice')
└─ [service documents similarly consolidated]

Phase 5: Finance Layer
├─ journal_entries + journal_lines → journal_entries + journal_lines (direct copy with immutable flag)
├─ accounts → accounts (validate GL account hierarchy)
├─ subledger mappings → subledger_documents + subledger_allocations (from old AP/AR posting)
├─ payments → payments + payment_allocations (from old payment_allocations)
├─ bank_transactions → bank_transactions + bank_reconciliations
└─ audit_logs initialization (from soft_deletes records and status_history)
```

---

## 3. Per-Module Mapping Overview

### 3.1 Foundation & Identity (Users, Roles, Org Structure)

**Legacy Tables:**
- `users`
- `roles` (if exists)
- `user_roles` (if exists)
- `org_units` (if exists)

**Canonical Tables:**
- `users`
- `roles`
- `permissions`
- `user_roles`
- `role_permissions`
- `org_units`
- `org_unit_closure`
- `user_org_units`

**Mapping Logic:**
- Users migrate 1:1 (email must be unique per tenant)
- Roles are rebuilt: legacy role names map to new RBAC model (Admin, Manager, Operator, Viewer + domain-scoped)
- org_units establish full closure tree in org_unit_closure for hierarchical queries
- user_org_units tracks which users belong to which org units (many-to-many)

**Transformation Rules:**
```sql
-- Users: Direct copy with tenant context
INSERT INTO canonical.users (id, tenant_id, first_name, last_name, email, phone, status, created_at, updated_at)
SELECT id, tenant_id, first_name, last_name, email, phone, 
       CASE WHEN deleted_at IS NOT NULL THEN 'inactive' ELSE 'active' END,
       created_at, updated_at
FROM legacy.users
WHERE deleted_at IS NULL OR deleted_at > DATE_SUB(NOW(), INTERVAL 3 YEAR);  -- Keep 3-year history

-- Roles: Map old role names to new structure
INSERT INTO canonical.roles (id, tenant_id, name, code, description)
SELECT 
  ROW_NUMBER() OVER (PARTITION BY tenant_id ORDER BY id),
  tenant_id,
  name,
  UPPER(REPLACE(name, ' ', '_')),
  CONCAT('Migrated from legacy: ', name)
FROM legacy.roles;
```

---

### 3.2 Party Master Data (Suppliers, Customers, Employees)

**Legacy Tables:**
- `suppliers` (~1000-5000 records)
- `customers` (~2000-10000 records)
- `employees` (~100-500 records)
- `supplier_contacts`, `customer_contacts`, `addresses`, etc. (fragmented)

**Canonical Tables:**
- `parties` (unified)
- `party_roles` (supplier, customer, employee)
- `party_contacts`
- `addresses`
- `party_addresses` (many-to-many)
- `tax_registrations`

**Key Transformation:**
```
SUPPLIER record:
  ├─ → parties (party_type='organization', name, tax_id, etc.)
  └─ → party_roles (role='supplier', party_id, is_active)

CUSTOMER record:
  ├─ → parties (party_type='organization', name, tax_id, etc.)
  └─ → party_roles (role='customer', party_id, is_active)

EMPLOYEE record:
  ├─ → parties (party_type='person', name, etc.)
  └─ → party_roles (role='employee', party_id, is_active, department, employee_number)

ADDRESSES:
  ├─ addresses (street, city, postal_code, etc.)
  └─ party_addresses (party_id, address_id, address_type, is_primary)
```

**Data Volume Estimates:**
- parties: ~15,000–20,000 (3-5x more than any single legacy entity due to role re-use)
- party_roles: ~25,000–30,000 (companies may have multiple roles)

**Validation Checks:**
- No duplicate tax_ids per party per tenant
- At least one address per primary supplier/customer
- All employees linked to valid org_units

---

### 3.3 Product & Catalog

**Legacy Tables:**
- `products` (~3000–10000 items)
- `product_variants` (if exists, may be free-form or missing)
- `product_categories` (categories, subcategories)
- `price_lists`, `price_list_items` (~100–500 price lists)
- `units_of_measure` (UOMs)

**Canonical Tables:**
- `products`
- `product_variants` (required; SKU uniqueness per tenant)
- `product_categories`
- `price_lists`
- `price_list_items`
- `uoms`
- `uom_conversions` (e.g., 1 box = 12 units)

**Key Transformation:**

```
PRODUCT record without variants:
  ├─ → products (id, tenant_id, name, description, status)
  └─ → product_variants (sku = product.sku or generate new, product_id, barcode, status)

PRODUCT record with variants:
  ├─ → products (parent/template)
  └─ → product_variants (one per size/color/etc., unique SKU per tenant)
```

**Validation Checks:**
- Every product has at least one active variant
- SKUs are unique within tenant scope
- UOM references are valid
- Product categories form a valid hierarchy (no cycles)

---

### 3.4 Inventory & Warehouse

**Legacy Tables:**
- `warehouses` (~5–50 per tenant)
- `warehouse_locations` (~100–5000 per warehouse)
- `batches` (lot/serial tracking)
- `inventory_balances` or stock quantity tables (current holdings)
- `stock_movements` (history)

**Canonical Tables:**
- `warehouses`
- `warehouse_locations` + `warehouse_location_closure`
- `inventory_lots` (batch/lot numbers)
- `inventory_serials` (serial numbers)
- `inventory_balances` (denormalized snapshot)
- `stock_movements` + `stock_movement_lines` (immutable history)
- `inventory_layers` (FIFO/weighted costing history)
- `stock_reservations`

**Key Transformation:**

```
WAREHOUSE_LOCATION with nested hierarchy:
  ├─ → warehouse_locations (id, warehouse_id, location_code, parent_id, ...)
  └─ → warehouse_location_closure (ancestor_id, descendant_id, depth)
       [transitive closure tree for efficient queries]

CURRENT INVENTORY (stock balance):
  ├─ → inventory_balances (tenant_id, product_id, warehouse_id, qty, value)
  ├─ → stock_movements (historical, immutable copy marked as 'legacy_import')
  └─ → inventory_layers (cost basis: FIFO layers, WEIGHTED layers, etc.)

BATCH/SERIAL tracking:
  ├─ → inventory_lots (batch_number, product_id, expiry_date, etc.)
  └─ → inventory_serials (serial_number, lot_id, status)
```

**Validation Checks:**
- Total inventory in `inventory_balances` = SUM(stock_movements) per product per warehouse
- Batch quantities match lot_id cross-references
- No negative inventory after migration
- All cost layers reconcile to GL (Inventory asset account balance)

**Data Volume Notes:**
- stock_movements: Usually 5–20x the current inventory quantity (high transaction volume)
- inventory_layers: Created from current costing method (FIFO stacks, weighted avg)

---

### 3.5 Commercial Documents (Orders, Invoices, Returns)

**Legacy Tables:**
- `purchase_orders` + `purchase_order_lines` (~10K–100K records)
- `grn_headers` + `grn_lines` (Goods Receipt Notes)
- `purchase_invoices` + `purchase_invoice_lines`
- `purchase_returns` + `purchase_return_lines`
- `sales_orders` + `sales_order_lines` (~20K–200K records)
- `sales_invoices` + `sales_invoice_lines`
- `sales_returns` + `sales_return_lines` (if exists)
- `service_workorders` + `service_lines` (if exists)
- `service_invoices` + `service_invoice_lines` (if exists)
- Possibly: `credit_notes`, `debit_notes`, `procurement_requests`, `transfer_orders`, etc.

**Canonical Tables:**
- `document_types` (~25 types: PurchaseOrder, PurchaseReceipt, PurchaseInvoice, PurchaseReturn, SalesQuote, SalesOrder, SalesPickList, PackingList, SalesInvoice, SalesReturn, ServiceQuote, ServiceWorkOrder, ServiceInvoice, DebitNote, CreditNote, TransferOrder, etc.)
- `commercial_documents` (unified header table, ~200K–2M rows)
- `commercial_document_lines` (unified line table, ~1M–10M rows)
- `commercial_document_taxes` (tax breakdown per line or document)
- `document_links` (PO→Receipt→Invoice chain, return links, etc.)
- `document_status_history` (immutable audit trail of status changes)

**Key Transformation:**

```
PURCHASE_ORDER (header):
  → commercial_documents (
      id, tenant_id, org_unit_id, document_type_id='PurchaseOrder',
      document_number=po_number, supplier_id→party_id,
      document_date=order_date, due_date=expected_date,
      status (mapped: draft→draft, confirmed→approved, received→approved, invoiced→fulfilled, closed→closed, cancelled→cancelled),
      subtotal, tax_total, grand_total,
      currency_id, exchange_rate,
      metadata={legacy_po_id, row_version}
    )

PURCHASE_ORDER_LINE (detail):
  → commercial_document_lines (
      id, tenant_id, document_id (from commercial_documents),
      line_sequence, product_id→product_variant_id (must resolve to variant),
      quantity=ordered_qty, unit_price, uom_id,
      discount_type, discount_value, discount_amount,
      line_total, tax_group_id, tax_amount,
      account_id (for GL posting),
      metadata={legacy_line_id, fulfillment_status, received_qty}
    )

GRN_HEADER (Goods Receipt Note):
  → commercial_documents (
      document_type_id='PurchaseReceipt',
      reference_id (links to purchase_order via document_links),
      received_date=grn_date
    )
  → document_links (from_document_id=PO, to_document_id=GRN, link_type='receipts')

PURCHASE_INVOICE:
  → commercial_documents (
      document_type_id='PurchaseInvoice',
      supplier_id→party_id,
      status (mapped appropriately),
      paid_amount, balance → stored in subledger_allocations instead
    )
  → document_links (from_document_id=GRN, to_document_id=Invoice, link_type='invoices')
  → subledger_documents (for AP posting)

PURCHASE_RETURN:
  → commercial_documents (
      document_type_id='PurchaseReturn',
      return_date,
      reference_id (original invoice)
    )
  → document_links (from_document_id=Invoice, to_document_id=Return, link_type='returns')
```

**Status Mapping Matrix:**

```
PURCHASE:
  Draft            → 'draft'
  Confirmed        → 'approved'
  Partial (received) → 'approved'  (partial fulfillment tracked in line metadata)
  Received         → 'approved'
  Invoiced         → 'fulfilled'
  Closed           → 'closed'
  Cancelled        → 'cancelled'

SALES:
  Draft            → 'draft'
  Confirmed        → 'approved'
  Partial (shipped) → 'approved'
  Invoiced         → 'fulfilled'
  Closed           → 'closed'
  Cancelled        → 'cancelled'

SERVICE:
  Quote            → 'draft'
  Approved         → 'approved'
  In Progress      → 'in_progress'
  Invoiced         → 'fulfilled'
  Closed           → 'closed'
  Cancelled        → 'cancelled'
```

**Data Volume Warnings:**
- commercial_documents + commercial_document_lines: Often 50%–70% of database size
- Indexes required: (tenant_id, document_type_id, status), (tenant_id, party_id), (tenant_id, document_date)
- Partition candidates: by document_type_id and date range (monthly or quarterly partitions)

---

### 3.6 Finance & Accounting

**Legacy Tables:**
- `journal_entries` + `journal_lines` (~50K–500K records per year)
- `accounts` (GL master, ~200–2000 accounts)
- `fiscal_years`, `fiscal_periods` (calendar/fiscal periods)
- `payments` + `payment_allocations` (AP/AR payments, ~5K–100K)
- `bank_accounts`, `bank_transactions`, `bank_reconciliations` (if exists)
- `currencies`, `exchange_rates`

**Canonical Tables:**
- `journal_entries` + `journal_lines` (immutable, identical structure)
- `accounts`
- `fiscal_years`, `fiscal_periods`
- `subledger_documents` (links commercial docs to GL)
- `subledger_allocations` (splits commercial doc across GL accounts)
- `payments` + `payment_allocations` (reconciliation tracking)
- `bank_accounts`, `bank_transactions`, `bank_reconciliations`
- `currencies`, `exchange_rates`

**Key Transformation:**

```
JOURNAL_ENTRY (header):
  → journal_entries (
      id, tenant_id, fiscal_period_id, entry_date, reference,
      description, status='posted', metadata={legacy_id, is_migrated: true}
    )

JOURNAL_LINE (detail):
  → journal_lines (
      id, entry_id, account_id, debit_amount, credit_amount,
      description, metadata={legacy_id}
    )

PAYMENT (from legacy):
  → payments (
      id, tenant_id, payment_date, party_id (from supplier/customer),
      payment_method, amount, currency_id, exchange_rate,
      bank_account_id, reference,
      metadata={legacy_payment_id}
    )
  → payment_allocations (
      payment_id, subledger_document_id or commercial_document_id,
      allocated_amount
    )

BANK_TRANSACTION:
  → bank_transactions (
      id, tenant_id, bank_account_id, transaction_date, amount,
      description, reference,
      metadata={legacy_id, reconciliation_status}
    )
  → bank_reconciliations (
      bank_transaction_id, payment_id (if matched),
      reconciled_at, reconciled_by
    )
```

**Validation Checks:**
- All journal_entries balance: SUM(debit) = SUM(credit) per entry
- All payments allocate fully to invoices (no orphaned payments)
- Bank reconciliation: bank_transactions match payment_allocations
- GL account hierarchy is consistent (no cycles, parent-child relationships valid)
- GL trial balance matches before/after migration

**Data Archival Strategy:**
- Immutable journal_entries/journal_lines are never deleted
- bank_transactions older than 7 years move to archive table
- subledger_allocations older than 3 years move to archive table
- Keep GL trial balance views for each closed fiscal period

---

## 4. Transformation Templates & Utilities

### 4.1 General Column Mapping Patterns

| Legacy Type | Canonical Type | Transformation |
|-------------|----------------|-----------------|
| `id` (bigint) | `id` (bigint unsigned) | Direct copy |
| `tenant_id` (fk) | `tenant_id` (fk, required) | Direct copy; validate referenced tenant exists |
| `status` (string enum) | `status` (string enum) | Map using status_mapping table (see section 3.5) |
| `deleted_at` (timestamp null) | None (hard delete) | Exclude soft-deleted records; log to audit_logs |
| `created_by` (fk to users) | `created_by` (fk to users) | Direct copy; validate user exists |
| `updated_at` | `updated_at` | Direct copy |
| `metadata` (json) | `metadata` (json) | Add `{legacy_table, legacy_id, migrated_at}` |
| Currency decimals | `DECIMAL(20,6)` | Convert all to consistent precision |

### 4.2 UUID/ULID Generation for External IDs

For records that need external-facing identifiers (invoice numbers, PO numbers), migrate existing values but add ULID for new records:

```sql
-- Example: product_identifiers
INSERT INTO product_identifiers (id, tenant_id, product_id, identifier_type, identifier_value)
SELECT 
  ULID(), tenant_id, id, 'LEGACY_SKU', sku
FROM legacy.products
WHERE sku IS NOT NULL;
```

### 4.3 Handling Free-Form Data

For fields that were free-form strings (e.g., `status`), ensure valid enums exist:

```sql
-- Validation query
SELECT DISTINCT status, COUNT(*) as cnt
FROM legacy.purchase_orders
GROUP BY status
ORDER BY cnt DESC;

-- Map any unknown statuses to 'draft' or raise error
```

---

## 5. Data Validation & Reconciliation Rules

### 5.1 Pre-Migration Validation

Before any migration phase, run:

```sql
-- Check for referential integrity in legacy schema
SELECT 'missing_suppliers' as issue_type, COUNT(*) as count
FROM legacy.purchase_orders
WHERE supplier_id IS NOT NULL 
  AND supplier_id NOT IN (SELECT id FROM legacy.suppliers);

-- Check for orphaned lines
SELECT 'orphaned_po_lines' as issue_type, COUNT(*) as count
FROM legacy.purchase_order_lines
WHERE purchase_order_id NOT IN (SELECT id FROM legacy.purchase_orders);

-- Check for negative quantities
SELECT 'negative_quantities' as issue_type, COUNT(*) as count
FROM legacy.stock_movements
WHERE quantity < 0 AND movement_type NOT LIKE '%return%';
```

### 5.2 Post-Migration Reconciliation

After each phase, run:

```sql
-- Record count comparison
SELECT 'parties' as table_name, COUNT(*) FROM canonical.parties
UNION ALL
SELECT 'old_suppliers', COUNT(*) FROM legacy.suppliers
UNION ALL
SELECT 'old_customers', COUNT(*) FROM legacy.customers;

-- Amount reconciliation (for financial records)
SELECT 
  'grand_total_reconciliation' as check_name,
  SUM(grand_total) as legacy_total,
  (SELECT SUM(subtotal + tax_total - discount_total) FROM canonical.commercial_documents) as canonical_total;
```

### 5.3 Detailed Reconciliation Queries

See `ETL_RECONCILIATION_QUERIES.sql` in the same directory for 40+ validation queries.

---

## 6. Cutover Workflow

### Phase Execution Order

1. **Phase 1 (Foundation)**: Users, roles, org_units
2. **Phase 2 (Master Data)**: Parties, products, categories
3. **Phase 3 (Inventory)**: Warehouses, locations, lots, current balances
4. **Phase 4 (Commercial Documents)**: All order/receipt/invoice/return records
5. **Phase 5 (Finance)**: Journal entries, subledger mappings, payments, bank reconciliation

### Per-Phase Checklist

Each phase follows:
1. **Staging**: Transform legacy data into temporary staging tables
2. **Validation**: Run pre-insert reconciliation checks
3. **Insert**: Bulk insert into canonical tables
4. **Post-Validation**: Run reconciliation queries to verify accuracy
5. **Dual-Write**: (For open transactions) write both legacy and canonical for 1 week
6. **Cutover**: Switch application to read from canonical; log last legacy writes
7. **Archive**: Move legacy data to archive schema if successful; keep for 90 days

---

## 7. Error Handling & Recovery

### Transaction Rollback Strategy

Each phase runs within a single transaction:

```sql
BEGIN TRANSACTION;

-- Phase X transformation
-- ... insert statements ...

-- Reconciliation validation
IF (SELECT COUNT(*) FROM canonical.parties) < (
    SELECT COUNT(*) FROM legacy.suppliers 
    UNION ALL SELECT COUNT(*) FROM legacy.customers
) THEN
  ROLLBACK;
  RAISE ERROR 'Reconciliation failed: record count mismatch';
ELSE
  COMMIT;
END IF;
```

### Dead Letter Queue

Failed records are captured in an error log:

```sql
CREATE TABLE etl_error_log (
  id BIGINT PRIMARY KEY,
  migration_phase VARCHAR(50),
  legacy_table VARCHAR(100),
  legacy_id BIGINT,
  canonical_table VARCHAR(100),
  error_message TEXT,
  error_sql TEXT,
  created_at TIMESTAMP
);
```

---

## 8. Performance Tuning

### Indexing Strategy During Migration

1. **Disable non-PK indexes** on canonical tables before bulk insert
2. **Batch inserts** in 10K–50K row chunks
3. **Re-enable indexes** post-migration
4. **Rebuild statistics** on all tables

```sql
-- Disable indexes
ALTER TABLE canonical.commercial_documents DISABLE KEYS;

-- Bulk insert in batches
INSERT INTO canonical.commercial_documents (...) 
SELECT ... FROM staging_documents WHERE id BETWEEN @start AND @end;

-- Re-enable and rebuild
ALTER TABLE canonical.commercial_documents ENABLE KEYS;
ANALYZE TABLE canonical.commercial_documents;
```

### Estimated Migration Runtime

| Phase | Legacy Records | Canonical Records | Est. Time | Notes |
|-------|---|---|---|---|
| 1 (Foundation) | ~500 | ~500 | <1 min | Direct copies, small volume |
| 2 (Master Data) | ~15K | ~30K | 5–10 min | Party denormalization |
| 3 (Inventory) | ~50K | ~100K | 15–30 min | Location closure tree computation |
| 4 (Commercial) | ~500K | ~1.5M | 2–4 hours | Largest phase; index rebuild needed |
| 5 (Finance) | ~250K | ~300K | 30–60 min | Journal lines are high-volume |
| **TOTAL** | **~815K** | **~1.9M** | **3–6 hours** | Depends on hardware and indexing |

---

## 9. Documentation & Audit Trail

### Migration Log

Every migration run creates an entry:

```sql
INSERT INTO etl_migration_log (
  session_id, phase, legacy_table, canonical_table,
  record_count_legacy, record_count_canonical,
  duration_seconds, status, started_at, completed_at, notes
) VALUES (...);
```

### Source-to-Target Mapping Registry

Maintain a registry of every legacy ID → canonical ID mapping:

```sql
-- For parties (many-to-many)
CREATE TABLE etl_legacy_to_canonical_mapping (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  legacy_table VARCHAR(100),
  legacy_id BIGINT,
  canonical_table VARCHAR(100),
  canonical_id BIGINT,
  canonical_type VARCHAR(50),  -- e.g., 'supplier', 'customer', 'employee'
  tenant_id BIGINT,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Allows tracking e.g., legacy.suppliers[100] → canonical.parties[9245] + canonical.parties[9246]
```

---

## 10. Known Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|-----------|
| **Duplicate suppliers/customers** | Medium | High | Run deduplication script pre-migration; validate unique tax_ids |
| **Orphaned FK references** | Medium | High | Pre-migration integrity check; move to null or default party |
| **Free-form status values** | High | Medium | Map to closest valid enum; log unmapped values; manual review |
| **Missing product variants** | High | Low | Auto-generate one variant per product; assign SKU=product.sku |
| **Quantity mismatches** | Medium | High | Reconciliation query mandatory post-Phase 3; adjust as needed |
| **GL unbalance** | Low | Critical | Pre- and post-migration GL trial balance match required |
| **Timezone conversion issues** | Low | Low | Store all timestamps in UTC; document local timezone mappings |
| **Currency rounding errors** | Medium | Low | Enforce DECIMAL(20,6) throughout; round to 2 decimals only at output |

---

## 11. Conclusion

This ETL strategy provides a systematic, reversible path from the fragmented legacy schema to the unified canonical design. Each phase is self-contained, testable, and can be rolled back independently.

**Next Steps:**
1. Review and approve this mapping strategy
2. Generate phase-specific SQL scripts from templates in section 4
3. Execute staging transformation for Phase 1
4. Run pre-migration validation suite
5. Execute Phase 1 cutover with monitoring
6. Iterate through phases 2–5 following the same workflow

