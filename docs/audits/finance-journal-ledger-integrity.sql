-- AutoERP Finance journal/ledger integrity audit. READ ONLY.
-- Run against an authorized isolated MySQL 8+ restoration, not production.
-- Amounts in debit/credit are journal transaction currency, not base currency.
-- Findings require accounting review; do not auto-repair, infer rates or modify GL.

-- 1. Posted/reversed journal header, document line and ledger conservation.
WITH document_lines AS (
    SELECT tenant_id, journal_entry_id, COUNT(*) AS line_count,
           SUM(debit) AS debit_total, SUM(credit) AS credit_total
    FROM finance_journal_lines
    GROUP BY tenant_id, journal_entry_id
),
ledger_lines AS (
    SELECT tenant_id, journal_entry_id, COUNT(*) AS entry_count,
           SUM(debit) AS debit_total, SUM(credit) AS credit_total,
           SUM(CASE WHEN debit < 0 OR credit < 0
                     OR (debit = 0 AND credit = 0)
                     OR (debit > 0 AND credit > 0) THEN 1 ELSE 0 END) AS invalid_entry_count
    FROM finance_ledger_entries
    GROUP BY tenant_id, journal_entry_id
)
SELECT j.id AS journal_id, j.tenant_id, j.journal_number, j.status,
       j.total_debit AS header_debit, j.total_credit AS header_credit,
       COALESCE(d.line_count, 0) AS document_line_count,
       COALESCE(d.debit_total, 0) AS document_debit,
       COALESCE(d.credit_total, 0) AS document_credit,
       COALESCE(l.entry_count, 0) AS ledger_entry_count,
       COALESCE(l.debit_total, 0) AS ledger_debit,
       COALESCE(l.credit_total, 0) AS ledger_credit,
       COALESCE(l.invalid_entry_count, 0) AS invalid_entry_count
FROM finance_journal_entries AS j
LEFT JOIN document_lines AS d
    ON d.tenant_id = j.tenant_id AND d.journal_entry_id = j.id
LEFT JOIN ledger_lines AS l
    ON l.tenant_id = j.tenant_id AND l.journal_entry_id = j.id
WHERE j.status IN ('posted', 'reversed')
  AND (
      j.total_debit <> j.total_credit
      OR COALESCE(d.line_count, 0) = 0
      OR COALESCE(l.entry_count, 0) <> COALESCE(d.line_count, 0)
      OR j.total_debit <> COALESCE(d.debit_total, 0)
      OR j.total_credit <> COALESCE(d.credit_total, 0)
      OR j.total_debit <> COALESCE(l.debit_total, 0)
      OR j.total_credit <> COALESCE(l.credit_total, 0)
      OR COALESCE(l.invalid_entry_count, 0) > 0
  )
ORDER BY j.tenant_id, j.id;

-- 2. Ledger entries detached from their real journal/line/account/scope.
SELECT l.id AS ledger_entry_id, l.tenant_id,
       l.journal_entry_id, l.journal_line_id,
       CASE
           WHEN j.id IS NULL THEN 'missing_or_cross_tenant_journal'
           WHEN d.id IS NULL THEN 'missing_or_cross_tenant_journal_line'
           WHEN d.journal_entry_id <> j.id THEN 'journal_line_mismatch'
           WHEN l.account_id <> d.account_id THEN 'account_mismatch'
           WHEN (l.organization_unit_id <> j.organization_unit_id
                 OR (l.organization_unit_id IS NULL AND j.organization_unit_id IS NOT NULL)
                 OR (l.organization_unit_id IS NOT NULL AND j.organization_unit_id IS NULL)
                 OR d.organization_unit_id <> j.organization_unit_id
                 OR (d.organization_unit_id IS NULL AND j.organization_unit_id IS NOT NULL)
                 OR (d.organization_unit_id IS NOT NULL AND j.organization_unit_id IS NULL))
               THEN 'organization_mismatch'
           WHEN l.debit <> d.debit OR l.credit <> d.credit THEN 'amount_mismatch'
           WHEN l.debit < 0 OR l.credit < 0
                OR (l.debit = 0 AND l.credit = 0)
                OR (l.debit > 0 AND l.credit > 0) THEN 'invalid_ledger_amount'
           ELSE 'requires_review'
       END AS review_reason
FROM finance_ledger_entries AS l
LEFT JOIN finance_journal_entries AS j
    ON j.id = l.journal_entry_id AND j.tenant_id = l.tenant_id
LEFT JOIN finance_journal_lines AS d
    ON d.id = l.journal_line_id AND d.tenant_id = l.tenant_id
WHERE j.id IS NULL OR d.id IS NULL
   OR d.journal_entry_id <> j.id
   OR l.account_id <> d.account_id
   OR (l.organization_unit_id <> j.organization_unit_id
       OR (l.organization_unit_id IS NULL AND j.organization_unit_id IS NOT NULL)
       OR (l.organization_unit_id IS NOT NULL AND j.organization_unit_id IS NULL)
       OR d.organization_unit_id <> j.organization_unit_id
       OR (d.organization_unit_id IS NULL AND j.organization_unit_id IS NOT NULL)
       OR (d.organization_unit_id IS NOT NULL AND j.organization_unit_id IS NULL))
   OR l.debit <> d.debit OR l.credit <> d.credit
   OR l.debit < 0 OR l.credit < 0
   OR (l.debit = 0 AND l.credit = 0)
   OR (l.debit > 0 AND l.credit > 0)
ORDER BY l.tenant_id, l.id;
