# ERP Database Redesign Blueprint

## Purpose

This document defines a completely new database architecture for AutoERP.
It does not attempt to improve or preserve the old schema. It replaces the current fragmented design with a single canonical ERP data model that is:

- simple to understand
- scalable for large transactional workloads
- maintainable across modules
- normalized where it matters
- practical for Laravel migrations and long-term operations
- safe for reporting, archiving, and regulatory audit needs

## What Was Wrong In The Current Schema

The current repository contains multiple competing schema directions at the same time. The main architectural problems are:

1. Duplicate schema families
- The same entities exist in multiple tracks with different shapes: tenants, users, products, warehouses, journal entries, payments, stock movements.
- This creates migration conflicts, duplicated business logic, and reporting inconsistency.

2. Inconsistent identity strategy
- Different schema tracks use bigint IDs, ULIDs, and string tenant identifiers.
- This blocks a predictable cross-module FK strategy.

3. Repeated transaction structures
- Purchase, Sales, and Service each define their own header and line structures with mostly repeated financial and quantity logic.
- This duplicates logic for tax, status, totals, workflow, and document linking.

4. Weak foreign key discipline
- Many transactional tables declare relation columns without real FK constraints.
- This makes data drift likely over time.

5. Free-form status fields everywhere
- Status values are mostly plain strings without controlled transition rules.
- This creates invalid lifecycle states and expensive reporting cleanup logic.

6. Overuse of soft deletes on heavy transactional tables
- Soft deletes are fine for master data, but they cause table and index bloat on ledgers, logs, and high-volume movement lines.

7. Mixed module boundaries
- Business entities are split between module-specific copies instead of one canonical source of truth.
- Customer, supplier, employee, and contact data should not be modeled as isolated islands.

8. Reporting-unfriendly design
- The current shape is neither fully normalized nor intentionally denormalized.
- It risks slow joins, duplicate totals, and difficulty in building stable reporting marts.

9. Large-table growth risk
- Audit logs, stock movement lines, bank transactions, and journal lines will grow rapidly.
- The current design does not consistently prepare for partitioning, retention, or archive policies.

10. Migration fragility
- Some migration sets contain duplicate table creation or broken FK definitions.
- This is a strong sign that the current structure is not safe as the long-term production model.

## New Architecture Principles

1. One canonical schema only
2. Strict tenant isolation with tenant_id on all tenant-owned tables
3. Single source of truth for business parties
4. Unified commercial document engine instead of duplicated sales and purchase document families
5. Immutable financial and inventory history
6. Snapshot tables for operational speed, ledger tables for traceability
7. Archive-first lifecycle planning for large tables
8. Controlled naming rules and migration sequencing
9. Clean module boundaries without duplicating master data
10. Simple enough for normal developers to maintain

## Canonical Domain Model

The new schema is organized into these bounded domains:

1. Platform and tenancy
2. Identity and access
3. Organization and structure
4. Party master
5. Product and catalog
6. Warehouse and inventory
7. Commercial documents
8. Finance and subledger
9. Audit, attachments, and integration

## Canonical Table Inventory

### 1. Platform and tenancy
- tenants
- tenant_domains
- tenant_settings
- id_sequences

### 2. Identity and access
- users
- roles
- permissions
- user_roles
- role_permissions

### 3. Organization and structure
- org_units
- org_unit_closure
- user_org_units

### 4. Party master
- parties
- party_roles
- party_contacts
- addresses
- party_addresses
- tax_registrations

### 5. Product and catalog
- currencies
- exchange_rates
- uoms
- uom_conversions
- product_categories
- products
- product_variants
- product_identifiers
- price_lists
- price_list_items

### 6. Warehouse and inventory
- warehouses
- warehouse_locations
- warehouse_location_closure
- inventory_lots
- inventory_serials
- inventory_balances
- stock_movements
- stock_movement_lines
- inventory_layers
- inventory_layer_consumptions
- stock_reservations
- stock_count_sessions
- stock_count_lines
- inventory_adjustment_reasons

### 7. Commercial documents
- document_types
- commercial_documents
- commercial_document_lines
- commercial_document_taxes
- document_links
- document_status_history

### 8. Finance and subledger
- accounts
- fiscal_years
- fiscal_periods
- journal_entries
- journal_lines
- subledger_documents
- subledger_allocations
- payments
- payment_allocations
- bank_accounts
- bank_transactions
- bank_reconciliations

### 9. Audit and integration
- attachments
- audit_logs
- integration_outbox
- integration_inbox

## Why The New Model Is Better

1. It removes duplicated transaction schemas.
2. It gives one shared party model for customers, suppliers, employees, and legal entities.
3. It keeps operational stock snapshots separate from immutable stock history.
4. It provides a clean bridge between commercial activity and accounting.
5. It allows archive and cleanup policies without damaging current transactional performance.
6. It gives a stable base for Laravel modules and future data marts.

## Module Boundaries In The New Design

### Tenant module
Owns tenant lifecycle and environment settings only.
Does not own user, organization, or business-party logic.

### Identity module
Owns users, roles, permissions, and membership relationships.
Does not own customer or supplier records.

### Organization module
Owns structural hierarchy such as company, branch, division, department, workshop, and cost center style nodes.

### Party module
Owns legal and contact identity for all external and internal parties.
Customer, supplier, employee, and contact are roles, not separate master tables.

### Product module
Owns products, variants, identifiers, UOMs, categories, and pricing references.

### Warehouse and inventory module
Owns physical locations, stock movement, reservations, lots, serials, balance snapshots, and costing layers.

### Commercial module
Owns quotes, orders, shipments, invoices, returns, service job billing documents, and links between them through a unified document engine.

### Finance module
Owns chart of accounts, fiscal periods, journals, subledger, cash and bank, and reconciliation.

### Audit and integration module
Owns immutable audit records and async event delivery.

## Document Engine Strategy

The new design intentionally replaces duplicated tables like sales_orders, purchase_orders, sales_invoices, purchase_invoices, service_invoices, and various return tables with a configurable document engine.

Examples:

- document_type = sales_order
- document_type = purchase_order
- document_type = shipment
- document_type = sales_invoice
- document_type = purchase_invoice
- document_type = sales_return
- document_type = purchase_return
- document_type = service_job
- document_type = service_invoice

This keeps the system simple while still allowing document-specific behavior in the application layer.

## Inventory Strategy

The inventory design uses three layers:

1. stock_movements and stock_movement_lines
- immutable operational history

2. inventory_layers and inventory_layer_consumptions
- costing history for FIFO, FEFO, AVCO, and specific traceability

3. inventory_balances
- current stock snapshot for fast reads

This is the correct ERP pattern for both performance and auditability.

## Archiving Classification

### Permanent core master data
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

### Hot operational tables that need archive plans
- commercial_documents
- commercial_document_lines
- stock_movements
- stock_movement_lines
- inventory_layers
- journal_entries
- journal_lines
- subledger_documents
- payments
- bank_transactions

### High-volume logs with aggressive cleanup
- audit_logs
- integration_outbox
- integration_inbox
- document_status_history

## Soft Delete Policy

Use soft deletes only for low-volume master data where recovery matters.

Good soft delete candidates:
- users
- roles
- org_units
- parties
- products
- product_variants
- warehouses
- warehouse_locations
- price_lists

Avoid soft deletes on:
- audit_logs
- journal_entries
- journal_lines
- stock_movements
- stock_movement_lines
- inventory_layers
- bank_transactions
- integration_outbox

Those tables should be immutable or archived, not soft-deleted.

## Fastest Growing Tables

These should be partition-ready from day one:

1. audit_logs
2. stock_movement_lines
3. journal_lines
4. commercial_document_lines
5. inventory_layer_consumptions
6. document_status_history
7. integration_outbox
8. bank_transactions

## Critical Design Rules

1. Every tenant-owned table must include tenant_id.
2. Every FK must be real unless a polymorphic integration boundary is intentional.
3. Every heavy transactional table must have a leading tenant_id index.
4. Every status lifecycle must be controlled by application rules and documented allowed transitions.
5. Totals may be stored on headers for speed, but lines remain the source of truth.
6. History tables must be append-only.
7. No duplicate master tables for customer, supplier, and employee identity.

## Deliverables In This Blueprint Pack

- ERP_DATABASE_REDESIGN_BLUEPRINT.md
- ERP_DATABASE_TABLE_CATALOG.md
- ERP_DATABASE_REDESIGN_ERD.mmd
- ERP_DATABASE_INDEX_RETENTION_GUIDE.md
- ERP_DATABASE_MIGRATION_ARCHITECTURE.md

This pack is the canonical target for rebuilding the ERP database architecture from scratch.