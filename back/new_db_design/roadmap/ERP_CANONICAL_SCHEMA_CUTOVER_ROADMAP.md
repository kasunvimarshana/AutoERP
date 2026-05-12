# ERP Canonical Schema Cutover Roadmap

## Objective

This roadmap defines how to move AutoERP from the current fragmented database structure to the new canonical schema.

It assumes:
- the current schema is not stable enough to extend long-term
- the new canonical schema will be built as a greenfield target
- migration must minimize business risk and preserve transactional history

## Current-State Constraints

The repository currently contains multiple overlapping schema tracks and repeated business entities. That means a direct in-place rewrite is too risky. The safer path is staged replacement.

Primary risks:
1. duplicate table families for the same business concept
2. inconsistent key strategies across modules
3. missing or weak foreign keys in some transactional paths
4. repeated purchase, sales, and service document structures
5. high-volume tables without consistent retention strategy

## Recommended Migration Strategy

Use a strangler-style migration, not a big-bang replacement.

Phases:
1. Stabilize current production behavior
2. Build canonical schema in parallel
3. Backfill master data
4. Backfill transactional history in controlled waves
5. Dual-write selected new transactions
6. Switch reads to canonical reporting and operational services
7. Decommission legacy tables by module

## Phase 0: Freeze Legacy Schema Drift

Goal:
Stop introducing new business-critical tables into the old fragmented design.

Actions:
- freeze duplicate schema experiments
- mark the canonical blueprint as target architecture
- require architecture review for any new table creation
- create schema inventory and ownership map

Exit criteria:
- one approved source of truth for future schema work
- no more parallel master-table creation

## Phase 1: Build Canonical Schema In Parallel

Goal:
Create the new schema without affecting current production flows.

Actions:
- deploy the canonical migration pack to a separate database or schema namespace
- seed reference data
- verify core constraints and index creation
- add smoke tests for migrations and seeders

Exit criteria:
- canonical schema deploys cleanly from empty database
- reference seed pack runs successfully

## Phase 2: Backfill Core Masters First

Goal:
Move low-churn master data before transactional history.

Order:
1. tenants
2. users and roles
3. org units
4. parties
5. addresses and contacts
6. currencies and UOMs
7. products and variants
8. warehouses and locations
9. accounts and fiscal calendars

Rules:
- each legacy entity must map to exactly one canonical entity
- create legacy-to-canonical ID mapping tables during ETL
- do not reuse inconsistent legacy keys as direct canonical PKs

Recommended mapping tables:
- migration_map_tenants
- migration_map_users
- migration_map_org_units
- migration_map_parties
- migration_map_products
- migration_map_product_variants
- migration_map_warehouses
- migration_map_locations
- migration_map_accounts

Exit criteria:
- core masters validate with row counts and referential reconciliation
- canonical app services can resolve all key master data

## Phase 3: Backfill Open Transactional State

Goal:
Move the current live business position before full history.

Priority order:
1. open sales and purchase documents
2. open inventory balances
3. open reservations
4. open subledger balances
5. open bank reconciliation state

Important:
For go-live safety, open state matters more than full legacy history.

Rules:
- reconstruct current stock from validated movement or latest trusted snapshot
- reconcile AR and AP open balances against source ledgers
- reconcile GL opening balances before transactional switch

Exit criteria:
- operational balances match trusted legacy reports
- stock by product and warehouse is signed off
- open receivable and payable balances are signed off

## Phase 4: Backfill Historical Transactions

Goal:
Load historical records needed for audit, analytics, and traceability.

Recommended waves:
1. last 12 months hot history
2. prior 24 months warm history
3. older history into archive or reporting store only

By domain:
- commercial documents and lines
- stock movements and layer history
- journal entries and lines
- payments and allocations
- bank transactions
- audit logs

Rules:
- preserve legacy source reference in metadata
- do not attempt to normalize broken legacy history beyond practical cleanup rules
- record data-quality exceptions in migration exception tables

Recommended exception tables:
- migration_exceptions_parties
- migration_exceptions_documents
- migration_exceptions_inventory
- migration_exceptions_finance

Exit criteria:
- history is queryable and tied to canonical entities
- exception list is reviewed and accepted

## Phase 5: Dual-Write Period

Goal:
Reduce cutover risk by validating the new model while legacy remains active.

Recommended scope for dual write:
- new master data changes
- new commercial documents
- new inventory movements
- new journal postings
- new payments

Do not dual-write indefinitely.
Keep the period short and tightly monitored.

Validation during dual write:
- document count parity
- movement count parity
- daily stock delta comparison
- journal total comparison
- payment allocation comparison

Exit criteria:
- parity checks stable for agreed window
- critical defects resolved

## Phase 6: Read Switch

Goal:
Switch operational and reporting reads to the canonical schema.

Recommended order:
1. internal reporting dashboards
2. inventory inquiry screens
3. document inquiry screens
4. finance inquiry screens
5. transactional write services

Rules:
- switch read-only workloads first
- preserve rollback ability during each switch step
- monitor latency, counts, and reconciliation reports

Exit criteria:
- business users validate canonical outputs
- no blocking discrepancies remain

## Phase 7: Module-by-Module Legacy Shutdown

Goal:
Retire legacy schema areas safely.

Recommended shutdown order:
1. duplicate reference tables
2. duplicate commercial document tables
3. duplicate inventory support tables
4. legacy reporting helpers
5. obsolete audit and integration tables

Rules:
- mark tables read-only before dropping
- archive exports before destructive cleanup
- maintain mapping tables for traceability

Exit criteria:
- legacy module tables no longer serve active reads or writes
- archival exports are complete and checksum-verified

## Domain-Specific Migration Notes

## Tenant and identity
- unify users against a single canonical users table
- move customer, supplier, employee identity to parties plus role assignments
- keep external auth identifiers in metadata if needed

## Organization
- collapse branch, department, and org-unit style structures into org_units
- use closure table for hierarchy traversal

## Party migration
- merge customer and supplier masters into parties
- assign role_code values such as customer, supplier, employee
- move addresses and contacts into reusable canonical tables

## Product migration
- normalize product families and variants
- treat SKU as variant-level identity
- convert legacy barcode and identifier tables into product_identifiers

## Inventory migration
- prefer reconstructed current balance plus hot movement history
- if legacy movement quality is poor, trust validated stock snapshot for opening balance and retain raw history separately for audit lookup
- rebuild inventory layers only where costing quality is reliable

## Commercial documents
- map legacy sales, purchase, and service headers into commercial_documents with document_type_id
- map lines into commercial_document_lines
- use document_links to preserve source-target chains

## Finance migration
- migrate accounts and fiscal periods before journals
- load opening balances per fiscal period if full journal history is not migrated on day one
- reconcile subledger open items to GL control accounts

## Audit and integration
- move only useful audit history into canonical audit_logs
- archive noisy historical logs outside OLTP database
- reset integration_outbox and inbox with clean idempotency policy where practical

## Data Quality Rules Before Cutover

Required checks:
1. every active customer maps to one canonical party
2. every active supplier maps to one canonical party
3. every sellable SKU maps to one product variant
4. every warehouse and location has a valid tenant scope
5. every open receivable and payable item maps to one canonical subledger document
6. every posted financial balance reconciles to opening GL totals
7. stock on hand and stock reserved reconcile by product and warehouse

## Reconciliation Reports Required

Create these reports before cutover sign-off:
- tenant master counts comparison
- user and role comparison
- party role comparison
- product and variant comparison
- warehouse and location comparison
- stock by product and warehouse comparison
- open sales and purchase document comparison
- AR aging comparison
- AP aging comparison
- GL trial balance comparison
- payment and bank balance comparison

## Archive Policy During Migration

Do not load all legacy history into the new OLTP database if it is not operationally necessary.

Recommended policy:
- hot operational history into canonical OLTP
- old history into archive store or reporting database
- legacy exports retained in immutable storage

## Target Cutover Model

At steady state:
- canonical schema is the only write target
- reporting reads source from canonical marts or canonical OLTP
- legacy database becomes read-only archive
- cleanup jobs manage long-term data size

## Recommended Deliverables For Implementation

1. ETL mapping specification per legacy table
2. canonical import scripts by domain
3. reconciliation SQL pack
4. dual-write verification jobs
5. rollback playbook
6. legacy shutdown checklist

## Final Recommendation

The new schema should go live by controlled domain migration, not by trying to patch the old schema into coherence. The fastest safe path is:
- build canonical schema in parallel
- move masters first
- move open balances second
- move hot history third
- switch reads before final write cutover
- archive legacy aggressively after stabilization
