-- ═════════════════════════════════════════════════════════════════════════════════
-- PARTITION & ARCHIVE JOB TEMPLATES
-- Purpose: Manage database size, performance, and retention policies
-- Version: 1.0 | Date: 2026-05-10
-- Status: Ready for scheduling
-- ═════════════════════════════════════════════════════════════════════════════════

-- These templates implement the retention policies defined in:
-- back/new_db_design/blueprint_v2/ERP_DATABASE_INDEX_RETENTION_GUIDE.md
--
-- Key Tables Affected (Partition-Ready):
-- - audit_logs (daily retention policy)
-- - stock_movement_lines (monthly archival)
-- - journal_lines (quarterly archival for closed periods)
-- - bank_transactions (7-year retention)
-- - integration_outbox / integration_inbox (30-day retention)
-- - document_status_history (permanently retained)
--
-- Typical Schedule:
-- Daily: 02:00 AM UTC - Cleanup audit_logs (older than retention window)
-- Weekly: 03:00 AM Sunday UTC - Verify partitions, optimize tables
-- Monthly: 04:00 AM 1st day UTC - Archive old commercial documents
-- Quarterly: 05:00 AM on quarters UTC - Archive old financial records

-- ═════════════════════════════════════════════════════════════════════════════════
-- 1. PARTITION SETUP FOR HIGH-VOLUME TABLES
-- ═════════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────────
-- 1.1: AUDIT_LOGS - RANGE PARTITION BY MONTH
-- Retention: 24 months active, then archive
-- Growth: ~100-500K rows per month
-- ─────────────────────────────────────────────────────────────────────────────────

-- Create partitions for audit_logs by month
-- This allows efficient purging of old logs without full table locks

/*
ALTER TABLE audit_logs 
PARTITION BY RANGE (MONTH(created_at)) (
    PARTITION p_jan VALUES LESS THAN (2),
    PARTITION p_feb VALUES LESS THAN (3),
    PARTITION p_mar VALUES LESS THAN (4),
    PARTITION p_apr VALUES LESS THAN (5),
    PARTITION p_may VALUES LESS THAN (6),
    PARTITION p_jun VALUES LESS THAN (7),
    PARTITION p_jul VALUES LESS THAN (8),
    PARTITION p_aug VALUES LESS THAN (9),
    PARTITION p_sep VALUES LESS THAN (10),
    PARTITION p_oct VALUES LESS THAN (11),
    PARTITION p_nov VALUES LESS THAN (12),
    PARTITION p_dec VALUES LESS THAN (13),
    PARTITION p_other VALUES LESS THAN MAXVALUE
);
*/

-- ─────────────────────────────────────────────────────────────────────────────────
-- 1.2: STOCK_MOVEMENT_LINES - RANGE PARTITION BY DATE
-- Retention: 5 years online, then archive
-- Growth: ~50-200K rows per day (high-volume)
-- Partition: Monthly to allow quarterly archival batches
-- ─────────────────────────────────────────────────────────────────────────────────

/*
ALTER TABLE stock_movement_lines
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p_202401 VALUES LESS THAN (202402),
    PARTITION p_202402 VALUES LESS THAN (202403),
    -- ... one partition per month ...
    PARTITION p_202412 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/

-- ─────────────────────────────────────────────────────────────────────────────────
-- 1.3: JOURNAL_LINES - RANGE PARTITION BY FISCAL_PERIOD
-- Retention: All live periods, archive old closed periods
-- Growth: ~30-100K rows per period (quarterly)
-- Partition: By fiscal_period_id for natural business boundaries
-- ─────────────────────────────────────────────────────────────────────────────────

/*
ALTER TABLE journal_lines
PARTITION BY RANGE (fiscal_period_id) (
    PARTITION p_pre_2020 VALUES LESS THAN (202001),
    PARTITION p_2020_2021 VALUES LESS THAN (202101),
    PARTITION p_2021_2022 VALUES LESS THAN (202201),
    PARTITION p_2022_2023 VALUES LESS THAN (202301),
    PARTITION p_2023_2024 VALUES LESS THAN (202401),
    PARTITION p_2024_2025 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/

-- ─────────────────────────────────────────────────────────────────────────────────
-- 1.4: BANK_TRANSACTIONS - RANGE PARTITION BY YEAR
-- Retention: 7 years online, then archive
-- Growth: ~1-10K rows per day
-- Partition: By year for easy archival
-- ─────────────────────────────────────────────────────────────────────────────────

/*
ALTER TABLE bank_transactions
PARTITION BY RANGE (YEAR(transaction_date)) (
    PARTITION p_2019 VALUES LESS THAN (2020),
    PARTITION p_2020 VALUES LESS THAN (2021),
    PARTITION p_2021 VALUES LESS THAN (2022),
    PARTITION p_2022 VALUES LESS THAN (2023),
    PARTITION p_2023 VALUES LESS THAN (2024),
    PARTITION p_2024 VALUES LESS THAN (2025),
    PARTITION p_2025 VALUES LESS THAN (2026),
    PARTITION p_2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/

-- ─────────────────────────────────────────────────────────────────────────────────
-- 1.5: INTEGRATION_OUTBOX / INTEGRATION_INBOX - RANGE PARTITION BY DATE
-- Retention: 30 days active, then archive
-- Growth: ~5-50K rows per day (message queue)
-- Partition: Daily or weekly for aggressive cleanup
-- ─────────────────────────────────────────────────────────────────────────────────

/*
ALTER TABLE integration_outbox
PARTITION BY RANGE (TO_DAYS(created_at)) (
    PARTITION p_day_01 VALUES LESS THAN (TO_DAYS('2026-05-10')),
    PARTITION p_day_02 VALUES LESS THAN (TO_DAYS('2026-05-11')),
    -- ... one partition per day for 30 days + 7 day buffer ...
    PARTITION p_day_37 VALUES LESS THAN (TO_DAYS('2026-06-16')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/

-- ═════════════════════════════════════════════════════════════════════════════════
-- 2. DAILY CLEANUP JOBS (Run 02:00 AM UTC)
-- ═════════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 2.1: AUDIT_LOGS - PURGE LOGS OLDER THAN 24 MONTHS
-- Policy: Aggressive cleanup (database size management critical)
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `cleanup_audit_logs_daily`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
READS SQL DATA
COMMENT 'Daily cleanup: remove audit logs older than 24 months'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE deleted_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Delete audit logs older than 24 months
  DELETE FROM audit_logs
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 MONTH)
  AND tenant_id NOT IN (SELECT id FROM tenants WHERE is_demo = true)
  LIMIT 100000;  -- Batch delete to avoid locking
  
  SET deleted_count = ROW_COUNT();
  SET log_message = CONCAT('Deleted ', deleted_count, ' audit logs (created > 24mo ago) in ', 
                           TIMESTAMPDIFF(SECOND, start_time, NOW()), 's');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'cleanup_audit_logs', 'success', log_message, deleted_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- Schedule: EVERY 1 DAY at 02:00
-- CREATE EVENT cleanup_audit_logs_event
-- ON SCHEDULE EVERY 1 DAY STARTS '2026-05-10 02:00:00' UTC
-- DO CALL cleanup_audit_logs_daily();

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 2.2: INTEGRATION_OUTBOX - PURGE OLD MESSAGE BATCHES (30-day retention)
-- Policy: Clean queue to prevent bloat
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `cleanup_integration_outbox_daily`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Daily cleanup: purge integration_outbox messages > 30 days old'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE deleted_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Delete outbox messages older than 30 days (only if successfully delivered)
  DELETE FROM integration_outbox
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND status IN ('delivered', 'failed_final')  -- Only safe-to-delete statuses
  AND tenant_id NOT IN (SELECT id FROM tenants WHERE is_demo = true)
  LIMIT 50000;
  
  SET deleted_count = ROW_COUNT();
  SET log_message = CONCAT('Deleted ', deleted_count, ' outbox messages (> 30d old, delivered/failed)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'cleanup_integration_outbox', 'success', log_message, deleted_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 2.3: INTEGRATION_INBOX - PURGE OLD PROCESSED MESSAGES (30-day retention)
-- Policy: Clean processed messages
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `cleanup_integration_inbox_daily`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Daily cleanup: purge integration_inbox messages > 30 days old'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE deleted_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Delete inbox messages older than 30 days (only if processed)
  DELETE FROM integration_inbox
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND status = 'processed'
  AND tenant_id NOT IN (SELECT id FROM tenants WHERE is_demo = true)
  LIMIT 50000;
  
  SET deleted_count = ROW_COUNT();
  SET log_message = CONCAT('Deleted ', deleted_count, ' inbox messages (> 30d old, processed)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'cleanup_integration_inbox', 'success', log_message, deleted_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ═════════════════════════════════════════════════════════════════════════════════
-- 3. MONTHLY ARCHIVAL JOBS (Run 04:00 AM UTC on 1st of month)
-- ═════════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 3.1: ARCHIVE OLD COMMERCIAL DOCUMENTS (closed, > 36 months)
-- Policy: Move closed purchase/sales documents to archive
-- Rationale: Rare access after 3 years; reduces OLTP table size
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `archive_old_commercial_documents`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Monthly: archive commercial documents closed > 36 months ago'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE archived_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Move closed documents (no pending allocations/reservations) to archive table
  INSERT INTO commercial_documents_archive
  SELECT cd.* 
  FROM canonical.commercial_documents cd
  WHERE cd.status = 'closed'
    AND cd.updated_at < DATE_SUB(NOW(), INTERVAL 36 MONTH)
    AND NOT EXISTS (
      SELECT 1 FROM document_links dl
      WHERE (dl.from_document_id = cd.id OR dl.to_document_id = cd.id)
        AND dl.link_type NOT IN ('returns', 'amendments')  -- Keep active chains
    )
    AND NOT EXISTS (
      SELECT 1 FROM stock_reservations sr
      WHERE sr.commercial_document_id = cd.id
    )
  LIMIT 10000;
  
  SET archived_count = ROW_COUNT();
  
  -- Delete archived records from live table
  DELETE FROM canonical.commercial_documents
  WHERE id IN (
    SELECT id FROM commercial_documents_archive
    WHERE archive_batch = LAST_INSERT_ID()
  )
  LIMIT 10000;
  
  SET log_message = CONCAT('Archived ', archived_count, ' closed commercial documents (closed > 36mo ago)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'archive_commercial_documents', 'success', log_message, archived_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 3.2: ARCHIVE OLD STOCK MOVEMENTS (> 24 months)
-- Policy: Archive historical inventory transactions
-- Rationale: Rarely queried; can be archived to separate storage
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `archive_old_stock_movements`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Monthly: archive stock movements > 24 months old'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE archived_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Copy to archive
  INSERT INTO stock_movements_archive
  SELECT sm.*
  FROM stock_movements sm
  WHERE sm.created_at < DATE_SUB(NOW(), INTERVAL 24 MONTH)
  LIMIT 50000;
  
  SET archived_count = ROW_COUNT();
  
  -- Cascade delete lines from live table
  DELETE FROM stock_movement_lines
  WHERE stock_movement_id IN (
    SELECT id FROM stock_movements_archive
    WHERE archive_batch = LAST_INSERT_ID()
  );
  
  DELETE FROM stock_movements
  WHERE id IN (
    SELECT id FROM stock_movements_archive
    WHERE archive_batch = LAST_INSERT_ID()
  );
  
  SET log_message = CONCAT('Archived ', archived_count, ' stock movements (> 24mo old)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'archive_stock_movements', 'success', log_message, archived_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ═════════════════════════════════════════════════════════════════════════════════
-- 4. QUARTERLY ARCHIVAL JOBS (Run 05:00 AM UTC on quarter start)
-- ═════════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 4.1: ARCHIVE CLOSED FISCAL PERIODS (move journal entries to cold storage)
-- Policy: Archive GL transactions for closed fiscal periods
-- Rationale: Immutable once closed; can move to archive for compliance
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `archive_closed_fiscal_periods`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Quarterly: archive journal entries for closed fiscal periods (> 36mo)'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE archived_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Archive journal entries for closed periods older than 36 months
  INSERT INTO journal_entries_archive
  SELECT je.*
  FROM journal_entries je
  INNER JOIN fiscal_periods fp ON je.fiscal_period_id = fp.id
  WHERE fp.status = 'closed'
    AND fp.end_date < DATE_SUB(NOW(), INTERVAL 36 MONTH)
  LIMIT 100000;
  
  SET archived_count = ROW_COUNT();
  
  -- Cascade archive journal lines
  DELETE FROM journal_lines
  WHERE journal_entry_id IN (
    SELECT id FROM journal_entries_archive
    WHERE archive_batch = LAST_INSERT_ID()
  );
  
  DELETE FROM journal_entries
  WHERE id IN (
    SELECT id FROM journal_entries_archive
    WHERE archive_batch = LAST_INSERT_ID()
  );
  
  SET log_message = CONCAT('Archived ', archived_count, ' journal entries from closed periods (> 36mo)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'archive_closed_fiscal_periods', 'success', log_message, archived_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 4.2: ARCHIVE OLD BANK TRANSACTIONS (> 7 years)
-- Policy: Comply with banking retention requirements then archive
-- Rationale: Legal hold for 7 years; archive after
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `archive_old_bank_transactions`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Quarterly: archive bank transactions > 7 years old'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE archived_count INT;
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Archive old bank reconciliations
  INSERT INTO bank_reconciliations_archive
  SELECT br.*
  FROM bank_reconciliations br
  WHERE br.reconciled_at < DATE_SUB(NOW(), INTERVAL 7 YEAR);
  
  -- Cascade archive transactions
  INSERT INTO bank_transactions_archive
  SELECT bt.*
  FROM bank_transactions bt
  WHERE bt.transaction_date < DATE_SUB(NOW(), INTERVAL 7 YEAR);
  
  SET archived_count = ROW_COUNT();
  
  -- Delete from live tables
  DELETE FROM bank_reconciliations
  WHERE reconciled_at < DATE_SUB(NOW(), INTERVAL 7 YEAR);
  
  DELETE FROM bank_transactions
  WHERE transaction_date < DATE_SUB(NOW(), INTERVAL 7 YEAR);
  
  SET log_message = CONCAT('Archived ', archived_count, ' bank transactions (> 7 years old)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'archive_bank_transactions', 'success', log_message, archived_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ═════════════════════════════════════════════════════════════════════════════════
-- 5. WEEKLY OPTIMIZATION JOBS (Run 03:00 AM Sunday UTC)
-- ═════════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 5.1: ANALYZE & OPTIMIZE TABLES (statistics refresh)
-- Purpose: Keep optimizer informed of data distribution
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `optimize_tables_weekly`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Weekly: analyze and optimize high-volume tables'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE start_time DATETIME DEFAULT NOW();
  
  -- Analyze key tables for query optimizer statistics
  ANALYZE TABLE commercial_documents;
  ANALYZE TABLE commercial_document_lines;
  ANALYZE TABLE stock_movements;
  ANALYZE TABLE journal_entries;
  ANALYZE TABLE audit_logs;
  ANALYZE TABLE bank_transactions;
  ANALYZE TABLE payments;
  ANALYZE TABLE subledger_documents;
  
  SET log_message = 'Analyzed table statistics for 8 high-volume tables';
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'optimize_tables', 'success', log_message, 8,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────────
-- JOB 5.2: VERIFY PARTITION MAINTENANCE
-- Purpose: Ensure partitions are in sync and no orphaned data
-- ─────────────────────────────────────────────────────────────────────────────────

DELIMITER $$

CREATE PROCEDURE `verify_partitions_weekly`()
PROCEDURE_LANGUAGE SQL
DETERMINISTIC
READS SQL DATA
COMMENT 'Weekly: check partition health and row distribution'
BEGIN
  DECLARE log_message VARCHAR(500);
  DECLARE start_time DATETIME DEFAULT NOW();
  DECLARE uneven_partition_count INT;
  
  -- Check for severely uneven partitions (may indicate issues)
  SELECT COUNT(*)
  INTO uneven_partition_count
  FROM information_schema.PARTITIONS
  WHERE TABLE_SCHEMA = 'canonical_erp'
    AND TABLE_NAME IN ('audit_logs', 'stock_movement_lines', 'journal_lines', 'bank_transactions')
    AND PARTITION_EXPRESSION IS NOT NULL
    AND (PARTITION_NAME IS NOT NULL AND PARTITION_ROWS > 10000000);  -- Alert on huge partitions
  
  SET log_message = CONCAT('Partition health check: ', uneven_partition_count, 
                           ' partitions exceed 10M rows (may need splitting)');
  
  INSERT INTO maintenance_log (
    job_name, status, message, record_count, duration_seconds, executed_at
  ) VALUES (
    'verify_partitions', 'info', log_message, uneven_partition_count,
    TIMESTAMPDIFF(SECOND, start_time, NOW()), NOW()
  );
  
END$$

DELIMITER ;

-- ═════════════════════════════════════════════════════════════════════════════════
-- 6. MAINTENANCE LOG TABLE (for tracking all jobs)
-- ═════════════════════════════════════════════════════════════════════════════════

-- Create maintenance log if not exists
CREATE TABLE IF NOT EXISTS maintenance_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  job_name VARCHAR(100) NOT NULL,
  status ENUM('success', 'warning', 'error', 'info') NOT NULL,
  message TEXT,
  record_count INT,
  duration_seconds INT,
  executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_job_date (job_name, executed_at),
  INDEX idx_status (status)
);

-- ═════════════════════════════════════════════════════════════════════════════════
-- 7. ARCHIVE TABLE DEFINITIONS
-- ═════════════════════════════════════════════════════════════════════════════════

-- Archive tables should mirror live tables but be stored on slower media
-- Use ARCHIVE or compressed InnoDB storage engine for cost-effectiveness

CREATE TABLE IF NOT EXISTS commercial_documents_archive LIKE commercial_documents;
ALTER TABLE commercial_documents_archive ENGINE=ARCHIVE;
ALTER TABLE commercial_documents_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS commercial_document_lines_archive LIKE commercial_document_lines;
ALTER TABLE commercial_document_lines_archive ENGINE=ARCHIVE;
ALTER TABLE commercial_document_lines_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS stock_movements_archive LIKE stock_movements;
ALTER TABLE stock_movements_archive ENGINE=ARCHIVE;
ALTER TABLE stock_movements_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS stock_movement_lines_archive LIKE stock_movement_lines;
ALTER TABLE stock_movement_lines_archive ENGINE=ARCHIVE;
ALTER TABLE stock_movement_lines_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS journal_entries_archive LIKE journal_entries;
ALTER TABLE journal_entries_archive ENGINE=ARCHIVE;
ALTER TABLE journal_entries_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS journal_lines_archive LIKE journal_lines;
ALTER TABLE journal_lines_archive ENGINE=ARCHIVE;
ALTER TABLE journal_lines_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS bank_transactions_archive LIKE bank_transactions;
ALTER TABLE bank_transactions_archive ENGINE=ARCHIVE;
ALTER TABLE bank_transactions_archive ADD COLUMN archive_batch INT;

CREATE TABLE IF NOT EXISTS bank_reconciliations_archive LIKE bank_reconciliations;
ALTER TABLE bank_reconciliations_archive ENGINE=ARCHIVE;
ALTER TABLE bank_reconciliations_archive ADD COLUMN archive_batch INT;

-- ═════════════════════════════════════════════════════════════════════════════════
-- 8. SUMMARY: ESTIMATED SPACE SAVINGS
-- ═════════════════════════════════════════════════════════════════════════════════

/*
ESTIMATED DATABASE SIZE AFTER LIFECYCLE MANAGEMENT
(Typical enterprise multi-tenant ERP after 5 years of operation)

BEFORE Archival:
  - audit_logs (5 years)           ~2.5 GB
  - stock_movements (5 years)      ~4.0 GB
  - journal_entries (5 years)      ~1.5 GB
  - commercial_documents (5 years) ~3.0 GB
  - bank_transactions (5 years)    ~0.5 GB
  - integration queues (5 years)   ~0.3 GB
  -------------------------------------------
  TOTAL (WITHOUT ARCHIVAL)          ~11.8 GB

AFTER Archival (applying policies):
  - audit_logs (2 years, not 5)    ~1.0 GB (66% reduction)
  - stock_movements (2 years)      ~1.6 GB (60% reduction)
  - journal_entries (3.5 years)    ~1.1 GB (27% reduction)
  - commercial_docs (3 years)      ~2.3 GB (23% reduction)
  - bank_transactions (7 years)*   ~0.5 GB (0% for now)
  - integration (30 days)          ~0.001 GB (99% reduction)
  -------------------------------------------
  TOTAL (WITH ARCHIVAL)             ~6.5 GB (45% REDUCTION)

  *Bank transactions kept for compliance; archive after 7 years
  
  ARCHIVE STORAGE TIER (COMPRESSED):
  - All archived tables (ARCHIVE engine): ~3 GB compressed
  
  NET SAVINGS: ~4-5 GB on primary SSD, compliance archive on cold storage
*/

