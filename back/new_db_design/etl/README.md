# ETL & Database Migration Documentation

**Status:** Production-Ready  
**Version:** 1.0  
**Date:** 2026-05-10  

---

## Quick Navigation

This directory contains all documentation and procedures for migrating from the current fragmented ERP database schema to the new unified canonical schema. 

### 📋 Key Documents (Read in This Order)

| Document | Purpose | Audience | Duration |
|----------|---------|----------|----------|
| **ETL_MAPPING_STRATEGY.md** | High-level transformation logic and column mappings | Data architects, DBAs, ETL engineers | 20 min read |
| **PARTITION_AND_ARCHIVE_JOBS.sql** | Automated database maintenance jobs and size management | DBAs, DevOps | 15 min read |
| **ETL_RECONCILIATION_QUERIES.sql** | 60+ validation queries for post-migration data integrity | DBAs, Data analysts | Reference |
| **CUTOVER_EXECUTION_CHECKLIST.md** | Step-by-step cutover procedure with rollback plans | DBAs, Operations team | 45 min read |

---

## 🎯 Migration Goals

✅ **Data Fidelity:** Zero data loss; every record traced from legacy to canonical  
✅ **Referential Integrity:** All foreign keys validated; no orphaned records  
✅ **Audit Compliance:** Source references (legacy table/ID) in metadata  
✅ **Minimal Downtime:** 15–30 minutes application downtime during cutover  
✅ **Reversibility:** Rollback procedures for each phase; recovery time < 1 hour  
✅ **Size Management:** 45% reduction in active database size through lifecycle policies  

---

## 📊 Migration Statistics

| Metric | Current | Post-Migration | Improvement |
|--------|---------|---|---|
| Database Size | ~11.8 GB | ~6.5 GB | -45% |
| Number of Tables | ~200 | ~80 | -60% |
| Duplicate Entities | 12 | 1 | Unified |
| Data Loss | None planned | Zero | ✅ |
| Estimated Downtime | N/A | 15–30 min | Minimal |

---

## 🔄 Migration Phases (Sequential)

```
Phase 0: Pre-Cutover Validation (48 hours before)
  └─ Environment readiness, backup verification, stakeholder sign-off

Phase 1: Foundation Layer (T+0 to T+30 min)
  └─ Users, roles, org structure
  └─ Estimated: 15–30 minutes
  └─ Downtime: None

Phase 2: Master Data Layer (T+30 to T+45 min)
  └─ Parties (suppliers/customers/employees), products, categories
  └─ Estimated: 10–20 minutes
  └─ Downtime: None

Phase 3: Inventory Layer (T+45 to T+75 min)
  └─ Warehouses, locations, current balances, cost layers
  └─ Estimated: 20–35 minutes
  └─ Downtime: None (inventory freeze 5 min)

Phase 4: Commercial Documents (T+75 to T+240 min)
  └─ Orders, receipts, invoices, returns (~1.5M records)
  └─ Estimated: 60–180 minutes
  └─ Downtime: None (dual-write for open documents)

Phase 5: Finance Layer (T+240 to T+360 min)
  └─ GL entries, subledger mappings, payments, reconciliation
  └─ Estimated: 60–120 minutes
  └─ Downtime: 10 minutes (GL posting freeze)

Phase 6: Application Cutover (T+360 to T+480 min)
  └─ Switch application from legacy to canonical
  └─ Estimated: 30–120 minutes
  └─ Downtime: 15–30 minutes
  └─ Validation: 60 minutes

Phase 7: Legacy Archive & Shutdown (Days 1–90)
  └─ 90-day parallel operation, then archive legacy
  └─ Downtime: None
```

**Total Downtime:** 15–30 minutes  
**Total Time:** 3–6 hours execution + 90 days validation

---

## 📄 Document Summaries

### 1. ETL_MAPPING_STRATEGY.md

**Content:**
- Executive summary of architectural changes
- Migration philosophy (data fidelity, referential integrity, audit compliance)
- High-level sequence of all phases
- Detailed per-module mapping (Identity, Parties, Products, Inventory, Commercial, Finance)
- Transformation templates and column mapping patterns
- Data validation rules (pre-migration and post-migration)
- Cutover workflow and error handling
- Performance tuning guidance
- Risk matrix with mitigations

**Key Transformations:**
- Suppliers + Customers + Employees → Unified **parties** table
- 12 order/receipt/invoice tables → Unified **commercial_documents** table with document_type_id
- Free-form inventory locations → **warehouse_location_closure** tree
- Separate GL posting logic → Unified **subledger_documents** bridge

**Use When:**
- Planning the overall migration strategy
- Training data engineers on transformation rules
- Documenting source-to-target mappings for compliance

---

### 2. PARTITION_AND_ARCHIVE_JOBS.sql

**Content:**
- Partition setup for high-volume tables (audit_logs, stock_movements, journal_lines, etc.)
- 8 automated maintenance jobs (daily, weekly, monthly, quarterly)
- Stored procedures for cleanup, archival, and optimization
- Archive table definitions (ARCHIVE engine for cold storage)
- Maintenance log tracking
- Estimated space savings (45% reduction)

**Jobs Included:**
- `cleanup_audit_logs_daily` - Remove logs > 24 months
- `cleanup_integration_outbox_daily` - Clean message queue (30-day retention)
- `archive_old_commercial_documents` - Archive closed docs > 36 months
- `archive_old_stock_movements` - Archive movements > 24 months
- `archive_closed_fiscal_periods` - Archive GL for closed periods > 36 months
- `optimize_tables_weekly` - Analyze and refresh statistics
- `verify_partitions_weekly` - Check partition health

**Schedule:**
- Daily (02:00 AM): Cleanup
- Weekly (03:00 AM Sunday): Optimization
- Monthly (04:00 AM 1st): Archival
- Quarterly (05:00 AM): Deep archival

**Use When:**
- Setting up database maintenance automation
- Implementing lifecycle policies
- Managing database size and performance
- Scheduling cleanup jobs

---

### 3. ETL_RECONCILIATION_QUERIES.sql

**Content:**
- 60+ validation queries organized by phase
- Phase-specific checks (Phases 1–5)
- Cross-phase integration checks
- Summary validation report

**Validation Categories:**
- **Phase 1:** Tenant/user/role/org unit integrity
- **Phase 2:** Party consolidation, product SKU uniqueness, category hierarchies
- **Phase 3:** Inventory quantity reconciliation, warehouse location trees, negative inventory detection
- **Phase 4:** Document count/type validation, header-line totals, status progression, party references
- **Phase 5:** GL trial balance, account references, subledger integrity, payment allocations
- **Integration:** Cross-table referential integrity

**Expected Results:**
All queries should return **zero rows** (indicating no issues) or **expected positive counts** (validation passed).

**Use When:**
- Post-migration data validation
- Running automated test suite
- Troubleshooting data discrepancies
- Generating audit reports

---

### 4. CUTOVER_EXECUTION_CHECKLIST.md

**Content:**
- Phase 0: Pre-cutover validation (48 hours)
- Phase 1–6: Step-by-step execution checklists
- Phase 7: Legacy archive procedures
- Rollback procedures for each phase
- Stakeholder sign-off templates
- Post-cutover validation procedures
- Operational procedures (maintenance schedules)

**Key Sections:**
- Pre-cutover environment readiness
- Database backup verification
- Application dual-write validation
- Phase-by-phase execution (with SQL validation queries)
- GL trial balance reconciliation
- Application switch procedure
- 90-day monitoring period
- Legacy system archival (after 1 year)

**Rollback Procedures:**
Each phase includes rollback instructions (truncate + restore from backup).

**Use When:**
- Executing the actual database migration
- Training operations team
- Documenting cutover procedures
- Communicating timelines with stakeholders

---

## 🗂️ File Organization

```
back/new_db_design/
├── ETL_MAPPING_STRATEGY.md           # Transformation logic & mappings
├── ETL_RECONCILIATION_QUERIES.sql    # 60+ validation queries
├── PARTITION_AND_ARCHIVE_JOBS.sql    # Maintenance jobs & archival
├── CUTOVER_EXECUTION_CHECKLIST.md    # Step-by-step cutover procedure
├── README.md                          # This file
└── (migrations & seeders in parent dir)
```

---

## 🚀 How to Use This Documentation

### For Data Architects:
1. Read **ETL_MAPPING_STRATEGY.md** sections 1–2 (overview and philosophy)
2. Review section 3 (per-module mapping) for your module
3. Review section 7–9 (risks and documentation)

### For DBAs:
1. Read **ETL_MAPPING_STRATEGY.md** section 2–4 (philosophy and transformations)
2. Memorize **PARTITION_AND_ARCHIVE_JOBS.sql** (maintenance schedules)
3. Execute **ETL_RECONCILIATION_QUERIES.sql** after each phase
4. Follow **CUTOVER_EXECUTION_CHECKLIST.md** during cutover

### For Operations Teams:
1. Review **CUTOVER_EXECUTION_CHECKLIST.md** Phase 0 (pre-cutover checklist)
2. Execute Phases 1–6 following step-by-step instructions
3. Run validation queries from **ETL_RECONCILIATION_QUERIES.sql** post-phase
4. Complete post-cutover validation checklist

### For Auditors / Compliance:
1. Review **ETL_MAPPING_STRATEGY.md** section 2 (philosophy with audit trail focus)
2. Review **ETL_MAPPING_STRATEGY.md** section 9 (documentation & audit trail registry)
3. Review **CUTOVER_EXECUTION_CHECKLIST.md** Phase 7 (archive & retention)

---

## ✅ Pre-Cutover Checklist

Before proceeding with any migration phase, verify:

- [ ] **All migration files created:**
  - [ ] 5 canonical migration files (Foundation through Finance)
  - [ ] 5 reference data seeders
  - [ ] All files in `back/new_db_design/migrations/`
  - [ ] All files in `back/new_db_design/seeders/`

- [ ] **Architecture Blueprint completed:**
  - [ ] `back/new_db_design/blueprint_v2/ERP_DATABASE_REDESIGN_BLUEPRINT.md`
  - [ ] `back/new_db_design/blueprint_v2/ERP_DATABASE_TABLE_CATALOG.md`
  - [ ] `back/new_db_design/blueprint_v2/ERP_DATABASE_INDEX_RETENTION_GUIDE.md`
  - [ ] `back/new_db_design/blueprint_v2/ERP_DATABASE_MIGRATION_ARCHITECTURE.md`
  - [ ] `back/new_db_design/blueprint_v2/ERP_DATABASE_REDESIGN_ERD.mmd`

- [ ] **ETL Documentation completed:**
  - [ ] `back/new_db_design/etl/ETL_MAPPING_STRATEGY.md`
  - [ ] `back/new_db_design/etl/ETL_RECONCILIATION_QUERIES.sql`
  - [ ] `back/new_db_design/etl/PARTITION_AND_ARCHIVE_JOBS.sql`
  - [ ] `back/new_db_design/etl/CUTOVER_EXECUTION_CHECKLIST.md`
  - [ ] `back/new_db_design/etl/README.md` (this file)

- [ ] **Operational prerequisites:**
  - [ ] Database backups created and verified
  - [ ] Monitoring and alerting configured
  - [ ] Support team trained
  - [ ] Rollback procedures tested
  - [ ] Stakeholder sign-off obtained

---

## 🆘 Getting Help

### Troubleshooting During Migration:

| Issue | Reference | Action |
|-------|-----------|--------|
| Reconciliation query returns errors | ETL_RECONCILIATION_QUERIES.sql | Run validation query for that phase; check detailed output |
| GL imbalance after Phase 5 | CUTOVER_EXECUTION_CHECKLIST.md §5.4 | Execute rollback procedure; investigate GL posting logic |
| Document totals don't match | ETL_MAPPING_STRATEGY.md §3.5 | Run Q4.2–Q4.4 reconciliation queries; check tax/discount calculations |
| Performance degradation | PARTITION_AND_ARCHIVE_JOBS.sql | Run optimize_tables_weekly; disable/rebuild indexes |
| Data loss during cutover | CUTOVER_EXECUTION_CHECKLIST.md §6.6 | Execute rollback; restore from backup; retry |

### Escalation Path:

1. **DBA** - Database technical issues, performance, backups
2. **Data Governance** - Data reconciliation, validation failures
3. **Application Lead** - Application integration, dual-write, connectivity
4. **VP Operations** - Executive approval, timeline changes, stakeholder communication

---

## 📞 Post-Cutover Support

After go-live, refer to **CUTOVER_EXECUTION_CHECKLIST.md** sections:
- **7.1:** 90-day monitoring procedures
- **7.2–7.3:** Legacy archive procedures
- **Post-Cutover Operational Procedures:** Ongoing maintenance

---

## 📝 Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-05-10 | Database Architect | Initial release; all phases documented |

---

## 🔒 Compliance & Audit Trail

All migration activities are:
- **Logged:** maintenance_log table tracks all jobs and their results
- **Audited:** etl_legacy_to_canonical_mapping registry maps every legacy ID to canonical ID
- **Traceable:** All records include metadata with source reference (legacy_table, legacy_id, migrated_at)
- **Reversible:** Rollback procedures exist for each phase; recovery time < 1 hour

---

**For questions or issues, contact:** ___________________________

