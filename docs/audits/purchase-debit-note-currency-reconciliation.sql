-- AutoERP Purchase debit-note currency/invoice reconciliation: READ ONLY.
-- Execute manually on an authorized isolated copy of production-like MySQL data, after
-- the Purchase currency-column upgrade is applied. Do NOT use a production connection.
-- The output is investigation evidence only: it cannot authorize currency backfill,
-- foreign-exchange conversion, balance amendments, or Finance journal modification.

-- 1. Historical notes without a proven currency, including soft-deleted records.
SELECT n.tenant_id,
       COUNT(*) AS note_count,
       SUM(CASE WHEN n.currency_id IS NULL THEN 1 ELSE 0 END) AS currency_unresolved_count,
       SUM(CASE WHEN n.currency_id IS NULL AND n.allocated_amount <> 0 THEN 1 ELSE 0 END) AS unresolved_with_allocations_count
FROM purchase_debit_notes AS n
GROUP BY n.tenant_id
ORDER BY n.tenant_id;

-- 2. Historical returns without a proven currency.
SELECT r.tenant_id,
       COUNT(*) AS return_count,
       SUM(CASE WHEN r.currency_id IS NULL THEN 1 ELSE 0 END) AS currency_unresolved_count
FROM purchase_returns AS r
GROUP BY r.tenant_id
ORDER BY r.tenant_id;

-- 3. Historical debit-note credit allocations with missing references, currency,
-- supplier, invoice direction, or organization agreement. All amounts are untouched.
SELECT c.id AS allocation_id,
       c.tenant_id,
       c.credit_source_id AS purchase_debit_note_id,
       c.invoice_id,
       c.allocated_amount,
       c.organization_unit_id AS allocation_organization_unit_id,
       n.organization_unit_id AS note_organization_unit_id,
       i.organization_unit_id AS invoice_organization_unit_id,
       n.currency_id AS note_currency_id,
       i.currency_id AS invoice_currency_id,
       CASE
           WHEN n.id IS NULL THEN 'missing_purchase_debit_note'
           WHEN i.id IS NULL THEN 'missing_invoice'
           WHEN n.supplier_id IS NULL OR i.party_id IS NULL
             OR n.supplier_type IS NULL OR i.party_type IS NULL
             OR n.supplier_id <> i.party_id OR n.supplier_type <> i.party_type
             OR n.supplier_type <> 'supplier' OR i.party_type <> 'supplier'
               THEN 'supplier_mismatch'
           WHEN i.direction IS NULL OR i.direction <> 'inbound'
               THEN 'invoice_direction_mismatch'
           WHEN n.currency_id IS NULL OR i.currency_id IS NULL THEN 'unresolved_currency'
           WHEN n.currency_id <> i.currency_id THEN 'currency_mismatch'
           WHEN ((n.organization_unit_id <> i.organization_unit_id
                  OR (n.organization_unit_id IS NULL AND i.organization_unit_id IS NOT NULL)
                  OR (n.organization_unit_id IS NOT NULL AND i.organization_unit_id IS NULL))
             OR (c.organization_unit_id <> i.organization_unit_id
                  OR (c.organization_unit_id IS NULL AND i.organization_unit_id IS NOT NULL)
                  OR (c.organization_unit_id IS NOT NULL AND i.organization_unit_id IS NULL))) THEN 'organization_mismatch'
           ELSE 'other_scope_investigation'
       END AS review_reason
FROM invoice_credit_allocations AS c
LEFT JOIN purchase_debit_notes AS n
    ON n.id = c.credit_source_id AND n.tenant_id = c.tenant_id
LEFT JOIN invoices AS i
    ON i.id = c.invoice_id AND i.tenant_id = c.tenant_id
WHERE c.credit_source_type = 'purchase_debit_note'
  AND (
       n.id IS NULL OR i.id IS NULL
       OR n.supplier_id IS NULL OR i.party_id IS NULL
       OR n.supplier_type IS NULL OR i.party_type IS NULL
       OR n.supplier_id <> i.party_id OR n.supplier_type <> i.party_type
             OR n.supplier_type <> 'supplier' OR i.party_type <> 'supplier'
       OR i.direction IS NULL OR i.direction <> 'inbound'
       OR n.currency_id IS NULL OR i.currency_id IS NULL
       OR n.currency_id <> i.currency_id
       OR ((n.organization_unit_id <> i.organization_unit_id
                  OR (n.organization_unit_id IS NULL AND i.organization_unit_id IS NOT NULL)
                  OR (n.organization_unit_id IS NOT NULL AND i.organization_unit_id IS NULL))
             OR (c.organization_unit_id <> i.organization_unit_id
                  OR (c.organization_unit_id IS NULL AND i.organization_unit_id IS NOT NULL)
                  OR (c.organization_unit_id IS NOT NULL AND i.organization_unit_id IS NULL)))
  )
ORDER BY c.tenant_id, c.id;

-- 4. Debit-note amount conservation versus Invoice-owned credit allocations.
-- Differences require reconciliation, NOT automatic ledger/balance correction.
SELECT n.id AS purchase_debit_note_id,
       n.tenant_id,
       n.currency_id,
       n.amount,
       n.allocated_amount AS stored_allocated_amount,
       n.remaining_amount AS stored_remaining_amount,
       COALESCE(SUM(c.allocated_amount), 0) AS invoice_credit_allocation_sum
FROM purchase_debit_notes AS n
LEFT JOIN invoice_credit_allocations AS c
    ON c.credit_source_type = 'purchase_debit_note'
   AND c.credit_source_id = n.id
   AND c.tenant_id = n.tenant_id
GROUP BY n.id, n.tenant_id, n.currency_id,
         n.amount, n.allocated_amount, n.remaining_amount
HAVING n.amount <> n.allocated_amount + n.remaining_amount
    OR n.allocated_amount <> COALESCE(SUM(c.allocated_amount), 0)
ORDER BY n.tenant_id, n.id;

-- 5. Return -> GRN -> Purchase Order lineage for human source-evidence review.
-- An order's currency is a CANDIDATE, not authority to rewrite historical terms.
SELECT n.id AS purchase_debit_note_id,
       n.tenant_id,
       n.currency_id AS note_currency_id,
       r.id AS purchase_return_id,
       r.currency_id AS return_currency_id,
       g.id AS goods_receipt_note_id,
       o.id AS purchase_order_id,
       o.currency_id AS order_currency_id
FROM purchase_debit_notes AS n
JOIN purchase_returns AS r
    ON r.id = n.purchase_return_id AND r.tenant_id = n.tenant_id
LEFT JOIN goods_receipt_notes AS g
    ON r.source_type = 'goods_receipt_note'
   AND g.id = r.source_id AND g.tenant_id = r.tenant_id
LEFT JOIN purchase_orders AS o
    ON o.id = g.purchase_order_id AND o.tenant_id = g.tenant_id
WHERE n.currency_id IS NULL
   OR r.currency_id IS NULL
   OR (n.currency_id IS NOT NULL AND r.currency_id IS NOT NULL
       AND n.currency_id <> r.currency_id)
   OR (r.currency_id IS NOT NULL AND o.currency_id IS NOT NULL
       AND r.currency_id <> o.currency_id)
ORDER BY n.tenant_id, n.id;
