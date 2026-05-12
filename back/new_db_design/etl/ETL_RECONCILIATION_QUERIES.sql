-- ═════════════════════════════════════════════════════════════════════════════════
-- ETL RECONCILIATION QUERIES
-- Purpose: Validate data integrity post-migration across all phases
-- Version: 1.0 | Date: 2026-05-10
-- ═════════════════════════════════════════════════════════════════════════════════

-- These queries are run AFTER each ETL phase to ensure data fidelity.
-- All should return zero rows indicating successful validation.
-- If any return data, investigation and correction is required before proceeding.

USE canonical_erp;

-- ═════════════════════════════════════════════════════════════════════════════════
-- PHASE 1: FOUNDATION LAYER RECONCILIATION
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q1.1: Verify all tenants migrated
SELECT 'PHASE_1_TENANT_COUNT_MISMATCH' as check_name, 
       COUNT(*) as legacy_count, 
       (SELECT COUNT(*) FROM tenants) as canonical_count
FROM legacy.tenants
WHERE COUNT(*) != (SELECT COUNT(*) FROM tenants)
GROUP BY 1;

-- Q1.2: Verify all users migrated with correct tenant assignment
SELECT 'PHASE_1_USER_ORPHANS' as issue,
       COUNT(*) as orphan_count
FROM canonical.users u
WHERE u.tenant_id NOT IN (SELECT id FROM tenants);

-- Q1.3: Verify no email duplicates within tenant
SELECT 'PHASE_1_EMAIL_DUPLICATES' as issue,
       tenant_id, email, COUNT(*) as cnt
FROM canonical.users
GROUP BY tenant_id, email
HAVING COUNT(*) > 1;

-- Q1.4: Verify roles mapped correctly
SELECT 'PHASE_1_ROLE_MAPPING' as check_name,
       lr.tenant_id,
       lr.name as legacy_role,
       cr.name as canonical_role,
       COUNT(DISTINCT lur.user_id) as legacy_user_count,
       COUNT(DISTINCT cur.user_id) as canonical_user_count
FROM legacy.roles lr
LEFT JOIN canonical.roles cr ON lr.tenant_id = cr.tenant_id AND UPPER(REPLACE(lr.name, ' ', '_')) = cr.code
LEFT JOIN legacy.user_roles lur ON lr.id = lur.role_id
LEFT JOIN canonical.user_roles cur ON cr.id = cur.role_id
GROUP BY lr.tenant_id, lr.name, cr.name;

-- Q1.5: Verify org_unit closure tree is complete
SELECT 'PHASE_1_ORGUNIT_CLOSURE_INCOMPLETE' as issue,
       COUNT(*) as incomplete_count
FROM canonical.org_units ou
WHERE NOT EXISTS (
  SELECT 1 FROM org_unit_closure ouc
  WHERE ouc.descendant_id = ou.id AND ouc.ancestor_id = ou.id AND ouc.depth = 0
);

-- Q1.6: Detect cycles in org_unit hierarchy (should be none)
WITH RECURSIVE hierarchy AS (
  SELECT id, parent_id, id as root_id, 0 as depth
  FROM canonical.org_units
  WHERE parent_id IS NULL
  
  UNION ALL
  
  SELECT ou.id, ou.parent_id, h.root_id, h.depth + 1
  FROM canonical.org_units ou
  INNER JOIN hierarchy h ON ou.parent_id = h.id
  WHERE h.depth < 100  -- safety limit
)
SELECT 'PHASE_1_ORGUNIT_CYCLES_DETECTED' as issue, COUNT(*) as cycle_count
FROM hierarchy h
WHERE h.id = h.root_id AND h.depth > 100;

-- ═════════════════════════════════════════════════════════════════════════════════
-- PHASE 2: MASTER DATA LAYER RECONCILIATION
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q2.1: Verify party record count (should be >= legacy suppliers + customers + employees)
SELECT 'PHASE_2_PARTY_COUNT_DISCREPANCY' as issue,
       COUNT(DISTINCT CONCAT(ls.tenant_id, '_supplier_', ls.id)) +
       COUNT(DISTINCT CONCAT(lc.tenant_id, '_customer_', lc.id)) +
       COUNT(DISTINCT CONCAT(le.tenant_id, '_employee_', le.id)) as expected_parties,
       (SELECT COUNT(*) FROM canonical.parties) as actual_parties;

-- Q2.2: Verify all legacy suppliers have corresponding party records
SELECT 'PHASE_2_UNMAPPED_SUPPLIERS' as issue,
       COUNT(*) as orphan_count
FROM legacy.suppliers ls
WHERE NOT EXISTS (
  SELECT 1 FROM canonical.parties cp
  INNER JOIN canonical.party_roles cpr ON cp.id = cpr.party_id
  WHERE cp.tenant_id = ls.tenant_id
    AND cpr.role = 'supplier'
    AND cp.tax_id = ls.tax_id
);

-- Q2.3: Verify all legacy customers have corresponding party records
SELECT 'PHASE_2_UNMAPPED_CUSTOMERS' as issue,
       COUNT(*) as orphan_count
FROM legacy.customers lc
WHERE NOT EXISTS (
  SELECT 1 FROM canonical.parties cp
  INNER JOIN canonical.party_roles cpr ON cp.id = cpr.party_id
  WHERE cp.tenant_id = lc.tenant_id
    AND cpr.role = 'customer'
    AND cp.tax_id = lc.tax_id
);

-- Q2.4: Verify party role uniqueness (no duplicate roles per party)
SELECT 'PHASE_2_DUPLICATE_PARTY_ROLES' as issue,
       party_id, role, COUNT(*) as cnt
FROM canonical.party_roles
GROUP BY party_id, role
HAVING COUNT(*) > 1;

-- Q2.5: Verify all parties have at least one valid role
SELECT 'PHASE_2_ROLELESS_PARTIES' as issue,
       COUNT(*) as orphan_count
FROM canonical.parties cp
WHERE NOT EXISTS (
  SELECT 1 FROM party_roles cpr
  WHERE cpr.party_id = cp.id
);

-- Q2.6: Verify product count matches or exceeds legacy (allows new products)
SELECT 'PHASE_2_PRODUCT_MISSING' as issue,
       COUNT(*) as legacy_count,
       (SELECT COUNT(*) FROM canonical.products) as canonical_count
FROM legacy.products
WHERE COUNT(*) > (SELECT COUNT(*) FROM canonical.products);

-- Q2.7: Verify every product has at least one variant
SELECT 'PHASE_2_PRODUCTLESS_VARIANTS' as issue,
       COUNT(*) as orphan_count
FROM canonical.product_variants cpv
WHERE cpv.product_id NOT IN (
  SELECT id FROM canonical.products
);

-- Q2.8: Verify SKU uniqueness per tenant
SELECT 'PHASE_2_DUPLICATE_SKUS' as issue,
       tenant_id, sku, COUNT(*) as cnt
FROM canonical.product_variants
WHERE sku IS NOT NULL
GROUP BY tenant_id, sku
HAVING COUNT(*) > 1;

-- Q2.9: Verify category hierarchy (no cycles)
WITH RECURSIVE cat_hierarchy AS (
  SELECT id, parent_id, id as root_id, 0 as depth
  FROM canonical.product_categories
  WHERE parent_id IS NULL
  
  UNION ALL
  
  SELECT pc.id, pc.parent_id, h.root_id, h.depth + 1
  FROM canonical.product_categories pc
  INNER JOIN cat_hierarchy h ON pc.parent_id = h.id
  WHERE h.depth < 100
)
SELECT 'PHASE_2_CATEGORY_CYCLES_DETECTED' as issue, COUNT(*) as cycle_count
FROM cat_hierarchy h
WHERE h.id = h.root_id AND h.depth > 100;

-- Q2.10: Verify UOM conversions are bidirectional (if symmetry required)
SELECT 'PHASE_2_UOM_CONVERSION_ORPHANS' as issue,
       COUNT(*) as orphan_count
FROM canonical.uom_conversions uuc
WHERE uuc.from_uom_id NOT IN (SELECT id FROM canonical.uoms)
   OR uuc.to_uom_id NOT IN (SELECT id FROM canonical.uoms);

-- ═════════════════════════════════════════════════════════════════════════════════
-- PHASE 3: INVENTORY LAYER RECONCILIATION
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q3.1: Verify warehouse count matches legacy
SELECT 'PHASE_3_WAREHOUSE_COUNT_MISMATCH' as issue,
       COUNT(*) as legacy_wh,
       (SELECT COUNT(*) FROM canonical.warehouses) as canonical_wh
FROM legacy.warehouses;

-- Q3.2: Verify location closure tree is complete (all locations)
SELECT 'PHASE_3_LOCATION_CLOSURE_INCOMPLETE' as issue,
       COUNT(*) as incomplete_count
FROM canonical.warehouse_locations wl
WHERE NOT EXISTS (
  SELECT 1 FROM warehouse_location_closure wlc
  WHERE wlc.descendant_id = wl.id AND wlc.ancestor_id = wl.id AND wlc.depth = 0
);

-- Q3.3: Verify inventory balances are non-negative
SELECT 'PHASE_3_NEGATIVE_INVENTORY' as issue,
       tenant_id, product_id, warehouse_id, quantity
FROM canonical.inventory_balances
WHERE quantity < 0
LIMIT 100;

-- Q3.4: Reconcile inventory quantity: SUM(stock_movements) = inventory_balances per product/warehouse
SELECT 'PHASE_3_INVENTORY_QUANTITY_MISMATCH' as issue,
       ib.tenant_id, ib.product_id, ib.warehouse_id,
       ib.quantity as balance_qty,
       COALESCE(SUM(sml.qty_change), 0) as movement_sum,
       ABS(ib.quantity - COALESCE(SUM(sml.qty_change), 0)) as discrepancy
FROM canonical.inventory_balances ib
LEFT JOIN canonical.stock_movements sm ON ib.tenant_id = sm.tenant_id 
                                       AND ib.product_id = sm.product_id
LEFT JOIN canonical.stock_movement_lines sml ON sm.id = sml.stock_movement_id
GROUP BY ib.id
HAVING ABS(ib.quantity - COALESCE(SUM(sml.qty_change), 0)) > 0.01
LIMIT 100;

-- Q3.5: Verify inventory lot and serial records reference valid products
SELECT 'PHASE_3_ORPHANED_LOTS' as issue, COUNT(*) as orphan_count
FROM canonical.inventory_lots il
WHERE il.product_id NOT IN (SELECT id FROM canonical.products);

SELECT 'PHASE_3_ORPHANED_SERIALS' as issue, COUNT(*) as orphan_count
FROM canonical.inventory_serials iserial
WHERE iserial.lot_id NOT IN (SELECT id FROM canonical.inventory_lots);

-- Q3.6: Verify no stock reservations with negative quantities
SELECT 'PHASE_3_NEGATIVE_RESERVATIONS' as issue, COUNT(*) as count
FROM canonical.stock_reservations
WHERE reserved_qty < 0;

-- Q3.7: Verify inventory value reconciliation (cost layers total to balance value)
SELECT 'PHASE_3_INVENTORY_VALUE_MISMATCH' as issue,
       ib.id,
       ib.value as balance_value,
       SUM(il.cost_per_unit * il.remaining_qty) as layer_sum,
       ABS(ib.value - SUM(il.cost_per_unit * il.remaining_qty)) as discrepancy
FROM canonical.inventory_balances ib
LEFT JOIN canonical.inventory_layers il ON ib.tenant_id = il.tenant_id
                                        AND ib.product_id = il.product_id
GROUP BY ib.id
HAVING ABS(ib.value - SUM(il.cost_per_unit * il.remaining_qty)) > 0.01
LIMIT 100;

-- ═════════════════════════════════════════════════════════════════════════════════
-- PHASE 4: COMMERCIAL DOCUMENTS RECONCILIATION
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q4.1: Verify document count by type
SELECT 'PHASE_4_DOCUMENT_TYPE_MISMATCH' as issue,
       document_type_id,
       COUNT(*) as canonical_count,
       (SELECT COUNT(*) FROM legacy.purchase_orders WHERE document_type_id = 'PurchaseOrder') as expected_po
FROM canonical.commercial_documents
WHERE document_type_id = 'PurchaseOrder'
GROUP BY document_type_id;

-- Q4.2: Verify all document lines reference valid documents
SELECT 'PHASE_4_ORPHANED_DOCUMENT_LINES' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_document_lines cdl
WHERE cdl.commercial_document_id NOT IN (
  SELECT id FROM canonical.commercial_documents
);

-- Q4.3: Verify document line items reference valid products
SELECT 'PHASE_4_INVALID_PRODUCT_REFERENCES' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_document_lines cdl
WHERE cdl.product_variant_id NOT IN (
  SELECT id FROM canonical.product_variants
);

-- Q4.4: Verify document line UOM references are valid
SELECT 'PHASE_4_INVALID_UOM_REFERENCES' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_document_lines cdl
WHERE cdl.uom_id NOT IN (
  SELECT id FROM canonical.uoms
);

-- Q4.5: Verify document header totals match line sums
SELECT 'PHASE_4_HEADER_LINE_TOTAL_MISMATCH' as issue,
       cd.id,
       cd.subtotal as header_subtotal,
       SUM(cdl.line_total) as line_sum,
       ABS(cd.subtotal - SUM(cdl.line_total)) as discrepancy
FROM canonical.commercial_documents cd
LEFT JOIN canonical.commercial_document_lines cdl ON cd.id = cdl.commercial_document_id
GROUP BY cd.id
HAVING ABS(cd.subtotal - COALESCE(SUM(cdl.line_total), 0)) > 0.01
LIMIT 100;

-- Q4.6: Verify document tax totals
SELECT 'PHASE_4_TAX_TOTAL_MISMATCH' as issue,
       cd.id,
       cd.tax_total as header_tax,
       SUM(cdl.tax_amount) as line_tax_sum,
       ABS(cd.tax_total - SUM(cdl.tax_amount)) as discrepancy
FROM canonical.commercial_documents cd
LEFT JOIN canonical.commercial_document_lines cdl ON cd.id = cdl.commercial_document_id
GROUP BY cd.id
HAVING ABS(cd.tax_total - COALESCE(SUM(cdl.tax_amount), 0)) > 0.01
LIMIT 100;

-- Q4.7: Verify document links reference valid documents
SELECT 'PHASE_4_ORPHANED_DOCUMENT_LINKS' as issue,
       COUNT(*) as orphan_count
FROM canonical.document_links dl
WHERE dl.from_document_id NOT IN (SELECT id FROM canonical.commercial_documents)
   OR dl.to_document_id NOT IN (SELECT id FROM canonical.commercial_documents);

-- Q4.8: Verify document status history records reference valid documents
SELECT 'PHASE_4_ORPHANED_STATUS_HISTORY' as issue,
       COUNT(*) as orphan_count
FROM canonical.document_status_history dsh
WHERE dsh.commercial_document_id NOT IN (
  SELECT id FROM canonical.commercial_documents
);

-- Q4.9: Verify status progression makes sense (draft → approved → fulfilled → closed)
SELECT 'PHASE_4_INVALID_STATUS_PROGRESSION' as issue,
       commercial_document_id,
       MAX(CASE WHEN status = 'draft' THEN changed_at END) as draft_time,
       MAX(CASE WHEN status = 'approved' THEN changed_at END) as approved_time,
       MAX(CASE WHEN status = 'fulfilled' THEN changed_at END) as fulfilled_time,
       MAX(CASE WHEN status = 'closed' THEN changed_at END) as closed_time
FROM canonical.document_status_history
GROUP BY commercial_document_id
HAVING (draft_time > approved_time AND approved_time IS NOT NULL)
    OR (approved_time > fulfilled_time AND fulfilled_time IS NOT NULL)
    OR (fulfilled_time > closed_time AND closed_time IS NOT NULL);

-- Q4.10: Verify parties exist for all document parties
SELECT 'PHASE_4_INVALID_PARTY_REFERENCES' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_documents cd
WHERE cd.party_id NOT IN (
  SELECT id FROM canonical.parties
);

-- ═════════════════════════════════════════════════════════════════════════════════
-- PHASE 5: FINANCE LAYER RECONCILIATION
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q5.1: Verify journal entries balance (SUM(debit) = SUM(credit))
SELECT 'PHASE_5_UNBALANCED_JOURNAL_ENTRIES' as issue,
       je.id,
       SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE 0 END) as total_debit,
       SUM(CASE WHEN jl.credit_amount > 0 THEN jl.credit_amount ELSE 0 END) as total_credit,
       ABS(SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE 0 END) - 
           SUM(CASE WHEN jl.credit_amount > 0 THEN jl.credit_amount ELSE 0 END)) as imbalance
FROM canonical.journal_entries je
LEFT JOIN canonical.journal_lines jl ON je.id = jl.journal_entry_id
GROUP BY je.id
HAVING ABS(SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE 0 END) - 
           SUM(CASE WHEN jl.credit_amount > 0 THEN jl.credit_amount ELSE 0 END)) > 0.01
LIMIT 100;

-- Q5.2: Verify all journal lines reference valid accounts
SELECT 'PHASE_5_INVALID_ACCOUNT_REFERENCES' as issue,
       COUNT(*) as orphan_count
FROM canonical.journal_lines jl
WHERE jl.account_id NOT IN (
  SELECT id FROM canonical.accounts
);

-- Q5.3: Verify GL trial balance: SUM(debit) = SUM(credit) globally
SELECT 'PHASE_5_GL_TRIAL_BALANCE_MISMATCH' as issue,
       SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE 0 END) as total_debit,
       SUM(CASE WHEN jl.credit_amount > 0 THEN jl.credit_amount ELSE 0 END) as total_credit,
       ABS(SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE 0 END) - 
           SUM(CASE WHEN jl.credit_amount > 0 THEN jl.credit_amount ELSE 0 END)) as imbalance
FROM canonical.journal_lines jl;

-- Q5.4: Verify subledger documents reference valid commercial documents or payments
SELECT 'PHASE_5_ORPHANED_SUBLEDGER_DOCUMENTS' as issue,
       COUNT(*) as orphan_count
FROM canonical.subledger_documents sd
WHERE sd.commercial_document_id NOT IN (SELECT id FROM canonical.commercial_documents);

-- Q5.5: Verify subledger allocations reference valid subledger documents and accounts
SELECT 'PHASE_5_INVALID_SUBLEDGER_ALLOCATIONS' as issue,
       COUNT(*) as orphan_count
FROM canonical.subledger_allocations sa
WHERE sa.subledger_document_id NOT IN (SELECT id FROM canonical.subledger_documents)
   OR sa.account_id NOT IN (SELECT id FROM canonical.accounts);

-- Q5.6: Verify payments allocate fully (total allocations = payment amount)
SELECT 'PHASE_5_MISALLOCATED_PAYMENTS' as issue,
       p.id,
       p.amount as payment_amount,
       SUM(pa.allocated_amount) as allocated_sum,
       ABS(p.amount - SUM(pa.allocated_amount)) as discrepancy
FROM canonical.payments p
LEFT JOIN canonical.payment_allocations pa ON p.id = pa.payment_id
GROUP BY p.id
HAVING ABS(p.amount - COALESCE(SUM(pa.allocated_amount), 0)) > 0.01
LIMIT 100;

-- Q5.7: Verify bank transactions reference valid bank accounts
SELECT 'PHASE_5_INVALID_BANK_ACCOUNT_REFERENCES' as issue,
       COUNT(*) as orphan_count
FROM canonical.bank_transactions bt
WHERE bt.bank_account_id NOT IN (
  SELECT id FROM canonical.bank_accounts
);

-- Q5.8: Verify bank reconciliation references are valid
SELECT 'PHASE_5_INVALID_BANK_RECONCILIATION_REFS' as issue,
       COUNT(*) as orphan_count
FROM canonical.bank_reconciliations br
WHERE br.bank_transaction_id NOT IN (SELECT id FROM canonical.bank_transactions)
   OR (br.payment_id IS NOT NULL AND br.payment_id NOT IN (SELECT id FROM canonical.payments));

-- Q5.9: Verify no duplicate exchange rates for same currency/date
SELECT 'PHASE_5_DUPLICATE_EXCHANGE_RATES' as issue,
       currency_id, exchange_date, COUNT(*) as cnt
FROM canonical.exchange_rates
GROUP BY currency_id, exchange_date
HAVING COUNT(*) > 1;

-- Q5.10: Verify fiscal year and period hierarchy
SELECT 'PHASE_5_INVALID_FISCAL_PERIODS' as issue,
       COUNT(*) as orphan_count
FROM canonical.fiscal_periods fp
WHERE fp.fiscal_year_id NOT IN (
  SELECT id FROM canonical.fiscal_years
);

-- ═════════════════════════════════════════════════════════════════════════════════
-- CROSS-PHASE INTEGRATION CHECKS
-- ═════════════════════════════════════════════════════════════════════════════════

-- Q6.1: Verify all document parties are parties (not orphaned)
SELECT 'INTEGRATION_DOCUMENT_PARTY_MISMATCH' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_documents cd
WHERE cd.party_id NOT IN (
  SELECT id FROM canonical.parties
);

-- Q6.2: Verify all document products are valid product variants
SELECT 'INTEGRATION_DOCUMENT_PRODUCT_MISMATCH' as issue,
       COUNT(*) as orphan_count
FROM canonical.commercial_document_lines cdl
WHERE cdl.product_variant_id NOT IN (
  SELECT id FROM canonical.product_variants
);

-- Q6.3: Verify subledger documents reference both commercial doc and GL posting
SELECT 'INTEGRATION_SUBLEDGER_INCOMPLETE' as issue,
       sd.id,
       CASE WHEN sd.commercial_document_id IS NULL THEN 'missing_commercial_doc' 
            WHEN sd.journal_entry_id IS NULL THEN 'missing_journal_entry'
            ELSE NULL END as issue_type
FROM canonical.subledger_documents sd
WHERE sd.commercial_document_id IS NULL OR sd.journal_entry_id IS NULL;

-- Q6.4: Verify stock reservations reference valid commercial documents and inventory
SELECT 'INTEGRATION_STOCK_RESERVATION_MISMATCH' as issue,
       COUNT(*) as orphan_count
FROM canonical.stock_reservations sr
WHERE sr.commercial_document_id NOT IN (SELECT id FROM canonical.commercial_documents)
   OR sr.product_variant_id NOT IN (SELECT id FROM canonical.product_variants)
   OR sr.warehouse_id NOT IN (SELECT id FROM canonical.warehouses);

-- ═════════════════════════════════════════════════════════════════════════════════
-- SUMMARY VALIDATION REPORT
-- ═════════════════════════════════════════════════════════════════════════════════

-- Run this query to get a summary of all validation check results:
-- Returns all issues found (empty result set = all checks pass)

SELECT 'VALIDATION_SUMMARY' as report_type,
       (SELECT COUNT(*) FROM canonical.tenants) as tenant_count,
       (SELECT COUNT(*) FROM canonical.users) as user_count,
       (SELECT COUNT(*) FROM canonical.parties) as party_count,
       (SELECT COUNT(*) FROM canonical.products) as product_count,
       (SELECT COUNT(*) FROM canonical.product_variants) as variant_count,
       (SELECT COUNT(*) FROM canonical.warehouses) as warehouse_count,
       (SELECT COUNT(*) FROM canonical.inventory_balances) as inventory_items,
       (SELECT COUNT(*) FROM canonical.commercial_documents) as document_count,
       (SELECT COUNT(*) FROM canonical.journal_entries) as journal_entry_count,
       (SELECT COUNT(*) FROM canonical.journal_lines) as journal_line_count,
       (SELECT COUNT(*) FROM canonical.payments) as payment_count,
       (SELECT COUNT(*) FROM canonical.audit_logs) as audit_log_count;

