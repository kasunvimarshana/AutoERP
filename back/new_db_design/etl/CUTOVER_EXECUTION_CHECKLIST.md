# CANONICAL SCHEMA CUTOVER EXECUTION CHECKLIST

**Version:** 1.0  
**Date:** 2026-05-10  
**Status:** Ready for Execution  
**Expected Duration:** 3-6 hours online, 24-72 hours validation  

---

## Overview

This document provides step-by-step instructions for migrating the entire ERP database from the current fragmented schema to the new unified canonical schema. It is organized into 6 sequential phases, each with pre-conditions, execution steps, and rollback procedures.

**Critical Success Factors:**
1. ✅ All pre-cutover validation passed (see Phase 0)
2. ✅ Stakeholders notified 48 hours in advance
3. ✅ Database backups verified and accessible
4. ✅ Rollback procedures tested
5. ✅ Monitoring and alerting configured
6. ✅ Support team standing by with escalation path

---

## PHASE 0: PRE-CUTOVER VALIDATION (Execute 48 hours before cutover)

### 0.1: Environment Readiness Check

- [ ] **Database Infrastructure**
  - [ ] Primary database: Verify connectivity and version (MySQL 8.0+ or equivalent)
  - [ ] Replication lag: Confirm replication is within 1 second
  - [ ] Disk space: Verify free space ≥ 150% of largest table (commercial_documents)
  - [ ] Query cache: Disabled or not relying on cache for reads
  - [ ] Binary logging: Enabled for point-in-time recovery

- [ ] **Backup Verification**
  - [ ] Full backup of legacy schema completed successfully
  - [ ] Full backup of canonical schema template (baseline) created
  - [ ] Backup restoration tested on staging environment (dry-run successful)
  - [ ] Backup storage location: __________________________
  - [ ] Backup verified by: _________________ Date: _________

- [ ] **Application Readiness**
  - [ ] Application code built and deployed to staging
  - [ ] Application dual-write support activated (writes to both legacy + canonical)
  - [ ] Data sync validation between legacy and canonical verified
  - [ ] All critical APIs tested against canonical schema
  - [ ] Performance baseline established (query response times documented)

- [ ] **Monitoring & Alerting**
  - [ ] Database performance monitoring enabled
  - [ ] Error tracking/logging enabled
  - [ ] Slack/PagerDuty notifications configured
  - [ ] Query audit logging enabled
  - [ ] Replication monitoring enabled

### 0.2: Data Validation Against Legacy Schema

```sql
-- Run these queries against LIVE legacy database to establish baseline
-- Save results to compare post-migration

-- 1. Record counts by module
SELECT 'PRE_CUTOVER_BASELINE' as report_type,
       (SELECT COUNT(*) FROM legacy.tenants) as tenants,
       (SELECT COUNT(*) FROM legacy.users) as users,
       (SELECT COUNT(*) FROM legacy.suppliers) as suppliers,
       (SELECT COUNT(*) FROM legacy.customers) as customers,
       (SELECT COUNT(*) FROM legacy.products) as products,
       (SELECT COUNT(*) FROM legacy.purchase_orders) as purchase_orders,
       (SELECT COUNT(*) FROM legacy.sales_orders) as sales_orders,
       (SELECT COUNT(*) FROM legacy.purchase_invoices) as purchase_invoices,
       (SELECT COUNT(*) FROM legacy.journal_entries) as journal_entries,
       (SELECT COUNT(*) FROM legacy.payments) as payments,
       NOW() as recorded_at;

-- 2. GL Trial Balance pre-cutover
SELECT 'GL_TRIAL_BALANCE' as check_type,
       SUM(CASE WHEN debit_amount > 0 THEN debit_amount ELSE 0 END) as total_debit,
       SUM(CASE WHEN credit_amount > 0 THEN credit_amount ELSE 0 END) as total_credit,
       ABS(SUM(CASE WHEN debit_amount > 0 THEN debit_amount ELSE 0 END) - 
           SUM(CASE WHEN credit_amount > 0 THEN credit_amount ELSE 0 END)) as imbalance
FROM legacy.journal_lines;

-- 3. AR/AP aging summary
SELECT 'AR_SUMMARY' as type,
       COUNT(*) as invoice_count,
       SUM(balance) as total_ar
FROM legacy.sales_invoices
WHERE status NOT IN ('closed', 'cancelled');

SELECT 'AP_SUMMARY' as type,
       COUNT(*) as invoice_count,
       SUM(balance) as total_ap
FROM legacy.purchase_invoices
WHERE status NOT IN ('closed', 'cancelled');

-- 4. Open transaction summary (those being migrated in Phase 2)
SELECT 'OPEN_PURCHASES' as type, COUNT(*) as count
FROM legacy.purchase_orders WHERE status NOT IN ('closed', 'cancelled');

SELECT 'OPEN_SALES' as type, COUNT(*) as count
FROM legacy.sales_orders WHERE status NOT IN ('closed', 'cancelled');

SELECT 'OPEN_STOCK_ORDERS' as type, COUNT(*) as count
FROM legacy.stock_movements WHERE posted_date IS NULL;
```

**Actions:**
- [ ] Execute baseline queries and document results
- [ ] Store baseline report in: `cutover_baseline_2026-05-10.xlsx`
- [ ] Compare with post-phase counts (Phase 3 validation will verify 1:1 match)
- [ ] Sign-off by DBA: ____________ Date: ________

### 0.3: Application Dual-Write Validation (72 hours pre-cutover)

For 3 days before cutover, application writes to BOTH legacy and canonical schemas:

- [ ] **Dual-Write Monitoring**
  - [ ] Enable dual-write mode in application config
  - [ ] Monitor synchronization delay (target: < 100ms)
  - [ ] Verify data consistency between legacy and canonical writes
  - [ ] Sample 1000 records; verify canonical matches legacy exactly
  - [ ] Error rate < 0.01%

- [ ] **Reconciliation Report**
  - [ ] Data matches verified: Date _________ by _____________
  - [ ] No discrepancies found (or document and approve exceptions)
  - [ ] Performance impact acceptable (query times ± 5% of baseline)

### 0.4: Stakeholder Sign-Off

- [ ] **Technical Sign-Off**
  - [ ] DBA approval: ________________ Date: _________
  - [ ] Data governance approval: ________________ Date: _________
  - [ ] Security approval: ________________ Date: _________

- [ ] **Business Sign-Off**
  - [ ] CFO / Finance director approval: ________________ Date: _________
  - [ ] Operations manager approval: ________________ Date: _________
  - [ ] Customer success manager approval: ________________ Date: _________

- [ ] **Communication**
  - [ ] End-user notification email sent (48 hours in advance)
  - [ ] Internal team briefing completed
  - [ ] Support team trained on new schema
  - [ ] Escalation contacts confirmed and on-call

---

## PHASE 1: FOUNDATION LAYER MIGRATION (T+0 to T+30 minutes)

**Objective:** Migrate users, roles, organizational structure (foundation tables)

**Estimated Runtime:** 15–30 minutes  
**Downtime:** None (read-only metadata)  
**Rollback:** Automated (simple truncate + restore from backup)

### 1.1: Pre-Phase Checklist

- [ ] Application in read-only mode or dual-write mode enabled
- [ ] All Phase 0 validations passed
- [ ] Backup of canonical.tenants, canonical.users, canonical.roles created
- [ ] DBA on call and monitoring active
- [ ] Prepared statement templates validated

### 1.2: Execute Foundation Layer Migration

**Via Laravel Migration Script** (if available):

```bash
# Manual trigger or automated
php artisan migrate --path=back/new_db_design/migrations --step=1
# Executes: 2026_05_10_000001_create_foundation_schema.php
```

**Via Direct SQL** (if manual execution required):

```sql
-- Execute foundation migration SQL directly
-- (See back/new_db_design/migrations/2026_05_10_000001_create_foundation_schema.php)

-- Verify completion:
SELECT 'PHASE_1_COMPLETION_CHECK' as check_type,
       COUNT(*) as tenant_count
FROM canonical.tenants;

-- Expected output: (should match legacy.tenants count)
```

### 1.3: Post-Phase Validation

```sql
-- Q1.1: Record count validation
SELECT 'PHASE_1_RECORD_COUNTS' as check_type,
       (SELECT COUNT(*) FROM canonical.tenants) as canonical_tenants,
       (SELECT COUNT(*) FROM legacy.tenants) as legacy_tenants,
       (SELECT COUNT(*) FROM canonical.users) as canonical_users,
       (SELECT COUNT(*) FROM legacy.users WHERE deleted_at IS NULL) as legacy_active_users;

-- Expected: Counts should match (canonical = legacy)

-- Q1.2: Foreign key integrity check
SELECT COUNT(*) as orphan_users
FROM canonical.users
WHERE tenant_id NOT IN (SELECT id FROM canonical.tenants);
-- Expected: 0

-- Q1.3: Role mapping verification
SELECT 'ROLE_MAPPING' as check,
       (SELECT COUNT(*) FROM canonical.roles) as canonical_roles,
       (SELECT COUNT(*) FROM legacy.roles) as legacy_roles;
```

**Actions:**
- [ ] All validation queries return expected results
- [ ] No orphaned records found
- [ ] Approve to proceed to Phase 2
- [ ] Approval signature: ________________ Date: _________

### 1.4: Rollback Procedure (if needed)

If Phase 1 validation FAILS:

```sql
-- Truncate canonical foundation tables
TRUNCATE TABLE user_org_units;
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE user_roles;
TRUNCATE TABLE permissions;
TRUNCATE TABLE roles;
TRUNCATE TABLE org_unit_closure;
TRUNCATE TABLE org_units;
TRUNCATE TABLE users;
TRUNCATE TABLE tenants;

-- Restore from pre-phase backup
RESTORE DATABASE canonical_erp FROM DISK = '/backups/canonical_pre_phase1_backup.bak';

-- Notify team and schedule retry
```

---

## PHASE 2: MASTER DATA LAYER MIGRATION (T+30 to T+45 minutes)

**Objective:** Migrate parties (suppliers, customers, employees), products, categories, UOMs

**Estimated Runtime:** 10–20 minutes  
**Downtime:** None  
**Rollback:** Automated (restore from backup)

### 2.1: Pre-Phase Checklist

- [ ] Phase 1 validation PASSED and approved
- [ ] Backup of current canonical.parties, canonical.products tables created
- [ ] ETL staging tables prepared (temp_parties, temp_products)
- [ ] DBA monitoring active

### 2.2: Execute Master Data Migration

```bash
php artisan migrate --path=back/new_db_design/migrations --step=1
# Executes: 2026_05_10_000002_create_party_and_catalog_schema.php
```

### 2.3: Validate Data Transformation

**Verify parties consolidation (suppliers + customers + employees → unified parties table):**

```sql
-- Q2.1: Party record count (many-to-many: one supplier may be both supplier AND customer)
SELECT 'PARTY_CONSOLIDATION_CHECK' as check,
       COUNT(DISTINCT CONCAT(ls.tenant_id, '_', ls.id)) as legacy_supplier_count,
       COUNT(DISTINCT CONCAT(lc.tenant_id, '_', lc.id)) as legacy_customer_count,
       COUNT(DISTINCT CONCAT(le.tenant_id, '_', le.id)) as legacy_employee_count,
       COUNT(*) as canonical_party_count
FROM canonical.parties cp;
-- Note: canonical_party_count may be LESS than sum due to consolidation

-- Q2.2: Role creation validation
SELECT 'PARTY_ROLES_CHECK' as check,
       role, COUNT(*) as party_count
FROM canonical.party_roles
GROUP BY role;

-- Expected: supplier, customer, employee roles present

-- Q2.3: Duplicate check (no party appears twice with same role in same tenant)
SELECT COUNT(*) as duplicates
FROM (
  SELECT party_id, role, tenant_id, COUNT(*) as cnt
  FROM canonical.party_roles
  GROUP BY party_id, role, tenant_id
  HAVING COUNT(*) > 1
) dup;
-- Expected: 0

-- Q2.4: Product SKU uniqueness
SELECT COUNT(*) as duplicate_skus
FROM (
  SELECT tenant_id, sku, COUNT(*) as cnt
  FROM canonical.product_variants
  WHERE sku IS NOT NULL
  GROUP BY tenant_id, sku
  HAVING COUNT(*) > 1
) dup;
-- Expected: 0

-- Q2.5: All products have variants
SELECT COUNT(*) as products_without_variants
FROM canonical.products cp
WHERE NOT EXISTS (
  SELECT 1 FROM canonical.product_variants cpv
  WHERE cpv.product_id = cp.id
);
-- Expected: 0
```

### 2.4: Reconciliation

Run comprehensive master data reconciliation:

```sql
-- Saved reconciliation queries from Phase 2 in ETL_RECONCILIATION_QUERIES.sql
-- Expected: All queries return 0 or expected positive counts
```

- [ ] Party count reconciliation PASSED
- [ ] Product count reconciliation PASSED
- [ ] SKU uniqueness check PASSED
- [ ] Category hierarchy validation PASSED
- [ ] Approve to proceed to Phase 3

---

## PHASE 3: INVENTORY LAYER MIGRATION (T+45 to T+75 minutes)

**Objective:** Migrate warehouses, locations, current stock balances, cost layers

**Estimated Runtime:** 20–35 minutes  
**Downtime:** None  
**Rollback:** Automated

### 3.1: Pre-Phase Checklist

- [ ] Phase 2 validation PASSED
- [ ] Inventory freeze window opened (no stock movements for 5 minutes during this phase)
- [ ] Backup of canonical inventory tables created
- [ ] Stock movement counter noted: ________________

### 3.2: Execute Inventory Layer Migration

```bash
php artisan migrate --path=back/new_db_design/migrations --step=1
# Executes: 2026_05_10_000003_create_inventory_schema.php
```

### 3.3: Inventory Quantity Reconciliation

**Critical:** Verify SUM(stock_movements) = inventory_balances per product

```sql
-- Q3.1: Inventory balance validation
SELECT 'INVENTORY_RECONCILIATION' as check,
       COUNT(*) as balance_records,
       SUM(quantity) as total_qty_on_hand,
       SUM(value) as total_inventory_value
FROM canonical.inventory_balances;

-- Q3.2: Detect negative inventory (should be none post-migration)
SELECT COUNT(*) as negative_inventory_items
FROM canonical.inventory_balances
WHERE quantity < 0;
-- Expected: 0

-- Q3.3: Reconcile quantities with stock movements
SELECT ib.id, ib.quantity as balance, 
       COALESCE(SUM(sml.qty_change), 0) as movement_sum,
       ABS(ib.quantity - COALESCE(SUM(sml.qty_change), 0)) as discrepancy
FROM canonical.inventory_balances ib
LEFT JOIN canonical.stock_movements sm ON ib.tenant_id = sm.tenant_id 
                                       AND ib.product_id = sm.product_id
LEFT JOIN canonical.stock_movement_lines sml ON sm.id = sml.stock_movement_id
GROUP BY ib.id
HAVING discrepancy > 0.01;
-- Expected: 0 rows (no discrepancies)

-- Q3.4: Warehouse location closure tree validation
SELECT COUNT(*) as incomplete_locations
FROM canonical.warehouse_locations wl
WHERE NOT EXISTS (
  SELECT 1 FROM warehouse_location_closure wlc
  WHERE wlc.descendant_id = wl.id AND wlc.ancestor_id = wl.id AND wlc.depth = 0
);
-- Expected: 0

-- Q3.5: Inventory value reconciliation (cost layers sum to balance value)
SELECT SUM(ABS(ib.value - COALESCE(SUM(il.cost_per_unit * il.remaining_qty), 0))) as total_value_variance
FROM canonical.inventory_balances ib
LEFT JOIN canonical.inventory_layers il ON ib.tenant_id = il.tenant_id
                                        AND ib.product_id = il.product_id
GROUP BY ib.id;
-- Expected: < $0.01 (rounding tolerance)
```

**Actions:**
- [ ] All quantity reconciliations PASSED
- [ ] No negative inventory
- [ ] All discrepancies resolved
- [ ] Inventory freeze window closed
- [ ] Approve to proceed to Phase 4

### 3.4: Rollback Procedure (if needed)

```sql
-- If reconciliation fails, truncate and restore:
TRUNCATE TABLE stock_reservations;
TRUNCATE TABLE inventory_layer_consumptions;
TRUNCATE TABLE inventory_layers;
TRUNCATE TABLE stock_movement_lines;
TRUNCATE TABLE stock_movements;
TRUNCATE TABLE inventory_balances;
TRUNCATE TABLE inventory_serials;
TRUNCATE TABLE inventory_lots;
TRUNCATE TABLE warehouse_location_closure;
TRUNCATE TABLE warehouse_locations;
TRUNCATE TABLE warehouses;

-- Restore from backup
RESTORE DATABASE canonical_erp FROM DISK = '/backups/canonical_pre_phase3_backup.bak';
```

---

## PHASE 4: COMMERCIAL DOCUMENTS MIGRATION (T+75 to T+240 minutes)

**Objective:** Migrate all orders, receipts, invoices, returns (~1.5M records)

**Estimated Runtime:** 60–180 minutes (varies by data volume)  
**Downtime:** For closed documents only (open documents dual-write)  
**Rollback:** Automated but time-consuming

### 4.1: Pre-Phase Checklist

- [ ] Phase 3 validation PASSED
- [ ] Commercial documents freeze window: New documents must use dual-write
- [ ] Backup of canonical commercial_documents tables created
- [ ] Index disabled on commercial_documents to speed bulk insert
- [ ] ETL staging tables prepared and validated

### 4.2: Execute Commercial Documents Migration (Batched)

For large document volumes (>500K records), execute in batches:

```bash
# Batch 1: Purchase orders and receipts
php artisan db:seed --class=MigrateClosedPurchaseDocumentsSeeder

# Batch 2: Purchase invoices
php artisan db:seed --class=MigrateClosedPurchaseInvoicesSeeder

# Batch 3: Sales orders and invoices
php artisan db:seed --class=MigrateClosedSalesDocumentsSeeder

# Batch 4: Service documents (if exists)
php artisan db:seed --class=MigrateClosedServiceDocumentsSeeder

# Batch 5: Open/draft documents (most recent)
php artisan db:seed --class=MigrateOpenCommercialDocumentsSeeder
```

### 4.3: Re-Enable Indexes & Analyze

```sql
-- After all batches inserted:
ALTER TABLE canonical.commercial_documents ENABLE KEYS;
ANALYZE TABLE canonical.commercial_documents;
ALTER TABLE canonical.commercial_document_lines ENABLE KEYS;
ANALYZE TABLE canonical.commercial_document_lines;
```

### 4.4: Validate Document Totals

```sql
-- Q4.1: Document count by type
SELECT 'DOCUMENT_TYPE_COUNT' as check,
       document_type_id, COUNT(*) as doc_count,
       (SELECT COUNT(*) FROM legacy.purchase_orders) as legacy_po_count,
       (SELECT COUNT(*) FROM legacy.purchase_invoices) as legacy_pi_count
FROM canonical.commercial_documents
GROUP BY document_type_id;

-- Q4.2: Header-line total reconciliation
SELECT COUNT(*) as header_line_mismatches
FROM canonical.commercial_documents cd
LEFT JOIN canonical.commercial_document_lines cdl ON cd.id = cdl.commercial_document_id
WHERE ABS(cd.subtotal - COALESCE(SUM(cdl.line_total), 0)) > 0.01
GROUP BY cd.id;
-- Expected: 0

-- Q4.3: Tax reconciliation
SELECT COUNT(*) as tax_mismatches
FROM canonical.commercial_documents cd
WHERE ABS(cd.tax_total - (
  SELECT COALESCE(SUM(tax_amount), 0)
  FROM canonical.commercial_document_lines
  WHERE commercial_document_id = cd.id
)) > 0.01;
-- Expected: 0

-- Q4.4: Amount reconciliation (grand_totals)
SELECT SUM(grand_total) as canonical_total,
       (SELECT SUM(grand_total) FROM legacy.purchase_orders 
        UNION ALL SELECT SUM(grand_total) FROM legacy.purchase_invoices
        UNION ALL SELECT SUM(grand_total) FROM legacy.sales_orders
        UNION ALL SELECT SUM(grand_total) FROM legacy.sales_invoices) as legacy_total
FROM canonical.commercial_documents;
-- Expected: Totals match within rounding tolerance
```

- [ ] Document counts match legacy
- [ ] Header-line totals reconcile
- [ ] Tax calculations correct
- [ ] Grand totals match
- [ ] Document links valid
- [ ] Status history complete

### 4.5: Open Transaction Validation

For documents migrated with `is_open=true`, verify they continue dual-write:

```sql
-- Q4.5: Open documents count
SELECT COUNT(*) as open_documents
FROM canonical.commercial_documents
WHERE status IN ('draft', 'approved', 'in_progress');

-- Expected: Matches count of open documents in legacy system
```

---

## PHASE 5: FINANCE LAYER MIGRATION (T+240 to T+360 minutes)

**Objective:** Migrate GL entries, subledger mappings, payments, bank reconciliation

**Estimated Runtime:** 60–120 minutes  
**Downtime:** For posted entries only  
**Rollback:** Automated

### 5.1: Pre-Phase Checklist

- [ ] Phase 4 validation PASSED
- [ ] GL posting freeze window open (no new entries for 10 minutes)
- [ ] Backup of canonical finance tables created
- [ ] GL trial balance baseline recorded

### 5.2: Execute Finance Layer Migration

```bash
php artisan migrate --path=back/new_db_design/migrations --step=1
# Executes: 2026_05_10_000005_create_finance_and_audit_schema.php
```

### 5.3: GL Balance Validation

**CRITICAL:** Verify General Ledger balances match legacy system:

```sql
-- Q5.1: GL Trial Balance validation
SELECT 'GL_TRIAL_BALANCE_POST_MIGRATION' as check,
       SUM(CASE WHEN debit_amount > 0 THEN debit_amount ELSE 0 END) as total_debit,
       SUM(CASE WHEN credit_amount > 0 THEN credit_amount ELSE 0 END) as total_credit,
       ABS(SUM(CASE WHEN debit_amount > 0 THEN debit_amount ELSE 0 END) - 
           SUM(CASE WHEN credit_amount > 0 THEN credit_amount ELSE 0 END)) as imbalance
FROM canonical.journal_lines;

-- Expected: Imbalance = 0.00 (GL must balance)

-- Q5.2: Balance sheet accounts reconciliation
SELECT a.account_code, a.account_name,
       SUM(jl.debit_amount - jl.credit_amount) as account_balance
FROM canonical.journal_lines jl
INNER JOIN canonical.accounts a ON jl.account_id = a.id
WHERE a.account_type IN ('asset', 'liability', 'equity')
GROUP BY a.id
ORDER BY account_balance;

-- Compare with legacy GL to verify account balances match

-- Q5.3: Income statement verification
SELECT a.account_code, a.account_name,
       SUM(jl.debit_amount - jl.credit_amount) as account_balance
FROM canonical.journal_lines jl
INNER JOIN canonical.accounts a ON jl.account_id = a.id
WHERE a.account_type IN ('revenue', 'expense')
  AND jl.journal_entry_id IN (
    SELECT je.id FROM journal_entries je
    INNER JOIN fiscal_periods fp ON je.fiscal_period_id = fp.id
    WHERE fp.status = 'closed'
  )
GROUP BY a.id
ORDER BY account_balance;

-- Q5.4: AR aging validation
SELECT DATEDIFF(NOW(), document_date) as days_old,
       COUNT(*) as invoice_count,
       SUM(grand_total) as total_ar
FROM canonical.commercial_documents
WHERE document_type_id IN ('SalesInvoice')
  AND status NOT IN ('closed', 'cancelled')
GROUP BY DATEDIFF(NOW(), document_date)
ORDER BY days_old;

-- Q5.5: AP aging validation
SELECT DATEDIFF(NOW(), document_date) as days_old,
       COUNT(*) as invoice_count,
       SUM(grand_total) as total_ap
FROM canonical.commercial_documents
WHERE document_type_id IN ('PurchaseInvoice')
  AND status NOT IN ('closed', 'cancelled')
GROUP BY DATEDIFF(NOW(), document_date)
ORDER BY days_old;

-- Q5.6: Payment allocation validation
SELECT COUNT(*) as unallocated_payment_count
FROM canonical.payments p
WHERE p.amount > (
  SELECT COALESCE(SUM(allocated_amount), 0)
  FROM canonical.payment_allocations
  WHERE payment_id = p.id
);
-- Expected: 0 (all payments fully allocated)
```

**Actions:**
- [ ] GL Trial Balance: BALANCED (imbalance = $0.00)
- [ ] Account balances verified against legacy GL
- [ ] AR/AP aging reconciled
- [ ] Payments fully allocated
- [ ] Approve to proceed to Phase 6

### 5.4: Rollback Procedure (if GL imbalance found)

```sql
-- If GL does not balance, rollback immediately:
TRUNCATE TABLE bank_reconciliations;
TRUNCATE TABLE bank_transactions;
TRUNCATE TABLE bank_accounts;
TRUNCATE TABLE payment_allocations;
TRUNCATE TABLE payments;
TRUNCATE TABLE subledger_allocations;
TRUNCATE TABLE subledger_documents;
TRUNCATE TABLE journal_lines;
TRUNCATE TABLE journal_entries;
TRUNCATE TABLE exchange_rates;
TRUNCATE TABLE fiscal_periods;
TRUNCATE TABLE fiscal_years;
TRUNCATE TABLE accounts;

-- Restore from backup
RESTORE DATABASE canonical_erp FROM DISK = '/backups/canonical_pre_phase5_backup.bak';
```

---

## PHASE 6: APPLICATION CUTOVER & VALIDATION (T+360 to T+480 minutes)

**Objective:** Switch application from legacy schema to canonical schema

**Estimated Runtime:** 30–120 minutes  
**Downtime:** 15–30 minutes for final validation + switch  
**Rollback:** Requires app restart + DB switch

### 6.1: Pre-Phase Checklist

- [ ] Phase 5 validation PASSED and GL balanced
- [ ] Application in read-only mode or dual-write confirmed
- [ ] DNS / connection pool configured for canonical database
- [ ] Application rollback plan documented
- [ ] Support team on standby with escalation path
- [ ] Customer communication ready to send

### 6.2: Final Dual-Write Sync (Last Hour Before Cutover)

Enable dual-write and verify canonical matches legacy exactly:

```sql
-- Sample 10,000 random records from high-impact tables
-- Compare canonical vs legacy values (must match 100%)

SELECT 'FINAL_SYNC_CHECK' as check_type,
       COUNT(*) as total_samples,
       COUNT(CASE WHEN match_status = 'match' THEN 1 END) as matching_records,
       COUNT(CASE WHEN match_status = 'mismatch' THEN 1 END) as mismatched_records
FROM (
  SELECT 'match' as match_status FROM canonical.commercial_documents cd
  WHERE EXISTS (
    SELECT 1 FROM legacy.purchase_orders po
    WHERE po.id = cd.legacy_id AND po.grand_total = cd.grand_total
  )
  LIMIT 10000
) sync_check;

-- Expected: mismatched_records = 0
```

- [ ] Dual-write verification: 100% match
- [ ] No discrepancies found
- [ ] Approve to proceed to application switch

### 6.3: Application Switch

**Step 1: Enable Read-Only Mode**

```bash
# Set application to read-only mode
# (API returns 503 "Service Maintenance" with 15-min banner)
php artisan down --message="Database migration in progress. Service will return in 15 minutes." --secret=migration_key_12345

# Notify all users
# Send email: "Database maintenance window starting now..."
```

- [ ] Application set to maintenance mode
- [ ] Users notified

**Step 2: Stop Dual-Write**

```bash
# Disable dual-write in application config
# (Only writes to canonical now)
APP_DUAL_WRITE_ENABLED=false
CANONICAL_SCHEMA_PRIMARY=true
```

**Step 3: Switch Database Connection**

```bash
# Update application database configuration
DB_PRIMARY_SCHEMA=canonical_erp  # Switch from legacy_erp to canonical_erp
DB_LEGACY_SCHEMA=legacy_erp_archive  # Retain for reference

# Restart application server
systemctl restart php-fpm
systemctl restart application-server
```

- [ ] Database connection switched to canonical
- [ ] Application restarted successfully
- [ ] No errors in application logs

### 6.4: Post-Cutover Validation (30-60 minutes)

**Immediate Validation (First 10 minutes):**

```sql
-- Q6.1: Verify canonical database is responsive
SELECT 1 as connectivity_test;
-- Expected: 1 (success)

-- Q6.2: Check for unusual error rates
SELECT COUNT(*) as error_count
FROM error_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
  AND level IN ('ERROR', 'CRITICAL');
-- Expected: < 10 (some expected due to switch)

-- Q6.3: Verify query performance
SELECT query, AVG(execution_time_ms) as avg_time_ms, MAX(execution_time_ms) as max_time_ms
FROM query_performance_log
WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
GROUP BY query
ORDER BY max_time_ms DESC
LIMIT 20;
-- Compare with Phase 0 baseline; should be within ±10%
```

- [ ] Database connectivity: OK
- [ ] Application error rate: Normal
- [ ] Query performance: Within baseline ±10%
- [ ] Application serving requests: OK

**Extended Validation (10–60 minutes post-cutover):**

```bash
# Run automated smoke tests against canonical schema
pytest tests/smoke_tests.py --database=canonical

# Validate all critical APIs
curl -X GET http://api.erp.local/api/products --header "Accept: application/json"
curl -X GET http://api.erp.local/api/orders --header "Accept: application/json"
curl -X GET http://api.erp.local/api/financials/gl --header "Accept: application/json"
```

- [ ] All smoke tests PASSED
- [ ] Critical API endpoints responding normally
- [ ] No significant query performance degradation

**Application Functionality Tests (60 minutes post-cutover):**

- [ ] Create new purchase order: Successfully created and saved
- [ ] Create new sales order: Successfully created and saved
- [ ] Create new invoice: Successfully created and saved
- [ ] Process payment: Successfully processed
- [ ] Post GL entry: Successfully posted and appears in trial balance
- [ ] Generate financial report: Report generates without error
- [ ] Export data to CSV: Export successful
- [ ] User login: All users can log in
- [ ] Permission checks: RBAC works correctly

### 6.5: Go-Live Decision

**If ALL validations passed:**

```bash
# Bring application out of maintenance mode
php artisan up --secret=migration_key_12345

# Send customer notification
# Email: "Database migration completed successfully. System is now live."

# Log cutover completion
echo "CANONICAL_SCHEMA_LIVE" | tee -a migration.log
```

- [ ] Application brought out of maintenance mode
- [ ] Customer notification sent
- [ ] Cutover marked as COMPLETE
- [ ] Executive sign-off: ________________ Date: _________

### 6.6: Rollback Procedure (if validation FAILED)

**If critical issues found post-cutover:**

```bash
# IMMEDIATE: Revert to legacy schema
# Step 1: Set app to maintenance mode
php artisan down --message="Reverting to previous system. Service will return in 15 minutes."

# Step 2: Switch database connection back to legacy
DB_PRIMARY_SCHEMA=legacy_erp
CANONICAL_SCHEMA_PRIMARY=false

# Step 3: Restart application
systemctl restart php-fpm
systemctl restart application-server

# Step 4: Enable dual-write (legacy writes to both while investigating)
APP_DUAL_WRITE_ENABLED=true

# Step 5: Notify team
# Email: "Database migration rolled back. Investigating issues. ETA to retry: [DEADLINE]"
```

- [ ] Application reverted to legacy schema
- [ ] Dual-write re-enabled
- [ ] Team notified
- [ ] Post-mortem scheduled: Date ________________ Time ________________

---

## PHASE 7: LEGACY SYSTEM ARCHIVE & SHUTDOWN (T+480 to T+2160 hours = 90 days)

**Objective:** Monitor canonical system for 90 days, then shut down legacy schema

**Duration:** 90 days observation period  
**Downtime:** None

### 7.1: 90-Day Monitoring Period

Run in parallel operation (canonical = primary, legacy = read-only reference):

- [ ] **Days 1–7: Active Monitoring**
  - [ ] Monitor error rates: Target < 0.1%
  - [ ] Monitor query performance: Baseline ±5%
  - [ ] Monitor system availability: Target 99.9%
  - [ ] Monitor user feedback: Document issues
  - [ ] Address any critical issues immediately

- [ ] **Days 8–30: Validation Period**
  - [ ] Run financial reconciliation weekly
  - [ ] Verify all GL accounts balance
  - [ ] Reconcile AR/AP aging with legacy system
  - [ ] Reconcile inventory quantities
  - [ ] Document any discrepancies

- [ ] **Days 31–90: Confidence Building**
  - [ ] Monthly GL reconciliation
  - [ ] Quarterly financial close successful
  - [ ] Monthly inventory count validated
  - [ ] No critical issues reported
  - [ ] Staff trained and proficient with new system

### 7.2: Legacy System Archival

**After 90 days of successful operation:**

```sql
-- Archive legacy schema (keep for 1 year for compliance)
CREATE SCHEMA legacy_erp_archive_2026q2;

-- Move legacy tables to archive schema
RENAME TABLE legacy_erp.* TO legacy_erp_archive_2026q2.*;

-- Optional: Compress archive for cold storage
mysqldump --all-databases --result-file=/archive/legacy_erp_2026q2.sql
gzip /archive/legacy_erp_2026q2.sql

-- Verify archive integrity
mysqlcheck --all-databases --archive
```

- [ ] Legacy schema archived successfully
- [ ] Archive backup verified
- [ ] Archive location documented: __________________________
- [ ] Archive retention policy: 1 year (delete 2027-Q2)

### 7.3: Legacy Database Shutdown

**After 1-year retention period:**

```sql
-- Final archival before deletion
BACKUP DATABASE legacy_erp_archive_2026q2 
TO DISK = '/archive/legacy_erp_final_2027q2.bak';

-- Drop archive schema
DROP SCHEMA legacy_erp_archive_2026q2;

-- Reclaim disk space
OPTIMIZE TABLE canonical_erp.*;
```

- [ ] Final backup created: Date ________________
- [ ] Backup verified and stored
- [ ] Archive schema dropped
- [ ] Disk space reclaimed: ________________ GB

---

## POST-CUTOVER OPERATIONAL PROCEDURES

### Schedule: Automated Maintenance Jobs

**Daily (02:00 AM UTC):**
```bash
php artisan schedule:run
# Executes: cleanup_audit_logs_daily, cleanup_integration_outbox_daily
```

**Weekly (03:00 AM Sunday UTC):**
```bash
# Run: optimize_tables_weekly, verify_partitions_weekly
php artisan db:optimize
```

**Monthly (04:00 AM 1st day UTC):**
```bash
# Run: archive_old_commercial_documents, archive_old_stock_movements
php artisan archive:commercial-documents
php artisan archive:stock-movements
```

**Quarterly (05:00 AM Q start day UTC):**
```bash
# Run: archive_closed_fiscal_periods, archive_old_bank_transactions
php artisan archive:fiscal-periods
php artisan archive:bank-transactions
```

### Ongoing Reconciliation Schedule

**Weekly:**
- [ ] Query performance analysis (compare to baseline)
- [ ] Error rate review

**Monthly:**
- [ ] GL trial balance reconciliation
- [ ] AR/AP aging reconciliation
- [ ] Inventory reconciliation

**Quarterly:**
- [ ] Financial close reconciliation
- [ ] GL account balance reconciliation
- [ ] Bank reconciliation

### Support & Escalation

**For Issues Post-Cutover:**

| Severity | Response Time | Escalation Path |
|----------|---------------|-----------------|
| Critical (system down) | 15 min | DBA → Dev Lead → VP Ops |
| High (data error) | 1 hour | DBA → Data Governance → Finance |
| Medium (performance) | 4 hours | DBA → Dev Team → PM |
| Low (documentation) | 24 hours | Dev Team → Knowledge Manager |

---

## FINAL CHECKLIST

- [ ] Pre-cutover validation PASSED (Phase 0)
- [ ] Foundation migration COMPLETED (Phase 1)
- [ ] Master data migration COMPLETED (Phase 2)
- [ ] Inventory migration COMPLETED (Phase 3)
- [ ] Commercial documents migration COMPLETED (Phase 4)
- [ ] Finance migration COMPLETED (Phase 5)
- [ ] Application cutover COMPLETED (Phase 6)
- [ ] 90-day monitoring period PASSED (Phase 7)
- [ ] Legacy system archived
- [ ] All automated jobs scheduled
- [ ] Support team trained
- [ ] Documentation updated

**Cutover Status:** ☐ NOT STARTED  ☐ IN PROGRESS  ☐ COMPLETED  ☐ ROLLED BACK

**Go-Live Approval:**
- Technical Lead: ______________________________ Date: __________
- Finance Director: ______________________________ Date: __________
- Operations Director: ______________________________ Date: __________

---

**Document Prepared By:** ______________________________ Date: __________

**Document Reviewed By:** ______________________________ Date: __________

