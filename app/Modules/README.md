# Enterprise ERP — Complete Laravel Migration Suite

## Architecture Overview

Multi-tenant SaaS ERP/CRM platform aligned with SAP, Oracle, and Microsoft Dynamics patterns.
GAAP/IFRS compliant. SOLID + DRY + KISS. Clean Architecture (Domain → Application → Infrastructure → Presentation).

---

## Module Structure & Migration Order

Migrations MUST be run in the order below due to foreign key dependencies.

```
app/Modules/
├── Core/               database/migrations/
│   ├── 2024_01_01_000001_create_tenants_table.php
│   ├── 2024_01_01_000002_create_organizations_table.php
│   ├── 2024_01_01_000003_create_currencies_table.php
│   ├── 2024_01_01_000004_create_exchange_rates_table.php
│   ├── 2024_01_01_000005_create_fiscal_years_table.php
│   └── 2024_01_01_000006_create_accounting_periods_table.php
│
├── Identity/           database/migrations/
│   ├── 2024_01_02_000001_create_users_table.php
│   ├── 2024_01_02_000002_create_roles_permissions_tables.php
│   ├── 2024_01_02_000003_create_parties_tables.php
│   └── 2024_01_02_000004_create_passport_oauth_tables.php
│
├── Finance/            database/migrations/
│   ├── 2024_01_03_000001_create_chart_of_accounts_table.php
│   ├── 2024_01_03_000002_create_journal_entries_tables.php
│   ├── 2024_01_03_000003_create_finance_support_tables.php
│   └── 2024_01_03_000004_create_invoice_lines_tables.php
│
├── Product/            database/migrations/
│   ├── 2024_01_04_000001_create_product_support_tables.php
│   └── 2024_01_04_000002_create_products_tables.php
│
├── Warehouse/          database/migrations/
│   └── 2024_01_05_000001_create_warehouse_tables.php
│
├── Inventory/          database/migrations/
│   └── 2024_01_06_000001_create_inventory_tables.php
│
├── Procurement/        database/migrations/
│   └── 2024_01_07_000001_create_procurement_tables.php
│
├── Sales/              database/migrations/
│   └── 2024_01_08_000001_create_sales_tables.php
│
├── Returns/            database/migrations/
│   └── 2024_01_09_000001_create_returns_tables.php
│
├── Traceability/       database/migrations/
│   └── 2024_01_10_000001_create_traceability_tables.php
│
├── Audit/              database/migrations/
│   └── 2024_01_11_000001_create_audit_tables.php
│
└── Config/             database/migrations/
    └── 2024_01_12_000001_create_config_tables.php
```

---

## Complete Table Inventory (57 tables)

### Core / Tenancy (6 tables)
| Table | Purpose |
|-------|---------|
| `tenants` | SaaS tenant registry; root of all isolation |
| `organizations` | Hierarchical org units (company → division → dept); self-referential |
| `currencies` | ISO 4217 currency master; one base per tenant |
| `exchange_rates` | Daily rates for multi-currency; source-tracked |
| `fiscal_years` | Financial year boundaries; closeable |
| `accounting_periods` | Period-based accrual accounting; status-controlled |

### Identity & Auth (8 tables)
| Table | Purpose |
|-------|---------|
| `users` | All system users; tenant-scoped |
| `roles` | RBAC roles; system roles protected |
| `permissions` | Granular action permissions (module.entity.action) |
| `role_permissions` | Many-to-many: roles ↔ permissions |
| `user_roles` | Many-to-many: users ↔ roles; optional org-unit scoping |
| `parties` | Unified customer/supplier/employee/partner master |
| `party_addresses` | Billing + shipping addresses per party |
| `party_contacts` | Contact persons per party |

### OAuth / Auth (3 tables)
| Table | Purpose |
|-------|---------|
| `oauth_access_tokens` | Laravel Passport bearer tokens |
| `oauth_refresh_tokens` | Refresh token pairs |
| `oauth_clients` | API client registry |
| `oauth_auth_codes` | Authorization code grant |
| `oauth_personal_access_clients` | PAT support |
| `user_device_sessions` | Multi-device session tracking |

### Financial & Accounting (10 tables)
| Table | Purpose |
|-------|---------|
| `chart_of_accounts` | Hierarchical CoA; GAAP/IFRS types; normal balance |
| `journal_entries` | Double-entry journal header; period-assigned |
| `journal_lines` | Debit/credit lines; sub-ledger party link |
| `bank_accounts` | GL-linked bank/cash/credit-card accounts |
| `bank_transactions` | Imported/manual transactions; auto-categorization |
| `tax_codes` | Tax rates; sales/purchase/both; GL-linked |
| `cost_centers` | Hierarchical cost center tree |
| `payment_terms` | Net-N, early-pay discount terms |
| `supplier_invoice_lines` | AP invoice line detail; 3-way match support |
| `customer_invoice_lines` | AR invoice line detail |

### Product & Catalog (10 tables)
| Table | Purpose |
|-------|---------|
| `categories` | Hierarchical product category tree |
| `brands` | Product brand master |
| `units_of_measure` | UoM master by type (qty, weight, volume…) |
| `uom_conversions` | Global + product-specific UoM conversion factors |
| `products` | Product master; all types; GL account links |
| `product_attributes` | Variant attributes (Color, Size, etc.) |
| `product_attribute_values` | Attribute value options |
| `product_variants` | SKU-level variants of variable products |
| `variant_attribute_values` | Variant ↔ attribute value mapping |
| `combo_items` | BOM-lite: combo/bundle components |
| `price_lists` | Named price lists (sale, purchase, transfer) |
| `product_prices` | Multi-price: tier, time-bound, UoM, currency |

### Warehouse & Location (2 tables)
| Table | Purpose |
|-------|---------|
| `warehouses` | Warehouse master (main, transit, quarantine…) |
| `locations` | Hierarchical bin locations (zone→aisle→rack→shelf→bin) |

### Inventory & Stock (7 tables)
| Table | Purpose |
|-------|---------|
| `batches` | Batch/lot master with expiry, manufacturer date |
| `serial_numbers` | Individual serial tracking with status |
| `stock_balances` | Real-time balance snapshot per product/variant/batch/location |
| `stock_movements` | Immutable movement ledger (receipt, issue, transfer, adjustment…) |
| `inventory_layers` | FIFO/LIFO/FEFO cost layers for valuation |
| `stock_reservations` | Soft reservations linked to orders |
| `cycle_counts` | Scheduled cycle count sessions |
| `cycle_count_lines` | Per-line count results and variances |

### Procurement (5 tables)
| Table | Purpose |
|-------|---------|
| `purchase_orders` | PO header; optional (SMB can skip) |
| `purchase_order_lines` | PO line items |
| `goods_receipts` | GRN header; PO optional (direct receive) |
| `goods_receipt_lines` | GRN lines with batch/serial/location |
| `supplier_invoices` | AP invoice; 3-way match; journal-linked |

### Sales & CRM (7 tables)
| Table | Purpose |
|-------|---------|
| `sales_orders` | SO header; optional (SMB direct invoice) |
| `sales_order_lines` | SO line items |
| `delivery_orders` | Delivery/shipment header |
| `delivery_lines` | Delivery lines with batch/serial/location |
| `customer_invoices` | AR invoice; journal-linked |
| `payments` | Inbound + outbound payments; multi-method |
| `payment_allocations` | Polymorphic payment ↔ invoice allocation |

### Returns Management (3 tables)
| Table | Purpose |
|-------|---------|
| `return_orders` | Return header; direction (from_customer / to_supplier) |
| `return_order_lines` | Return lines; condition-based; batch/serial optional |
| `credit_notes` | Credit note header; applied against invoices |

### Traceability & AIDC (3 tables)
| Table | Purpose |
|-------|---------|
| `identifiers` | Unified AIDC: barcode, QR, RFID-HF, RFID-UHF, NFC, GS1 EPC |
| `trace_logs` | Immutable EPCIS-style traceability ledger |
| `scan_sessions` | WMS scan session grouping |

### Audit & Compliance (2 tables)
| Table | Purpose |
|-------|---------|
| `audit_logs` | Polymorphic immutable audit trail (old/new JSON snapshots) |
| `attachments` | Polymorphic file attachments (multipart/form-data) |

### Configuration (4 tables)
| Table | Purpose |
|-------|---------|
| `settings` | Per-tenant, per-module key-value config store |
| `numbering_sequences` | Auto-numbering with prefix/suffix/padding/reset |
| `bank_categorization_rules` | Pattern-matching rules for bank transaction auto-categorization |
| `notification_preferences` | Per-user notification channel preferences |

---

## Key Design Decisions

### 1. Period-Based Accrual Accounting (GAAP/IFRS)
Every financial document (`purchase_orders`, `goods_receipts`, `supplier_invoices`,
`customer_invoices`, `sales_orders`, `payments`, `return_orders`, `credit_notes`,
`journal_entries`) carries a `period_id` → `accounting_periods`.

- **entry_date** = business transaction date
- **post_date** = accounting posting date (can differ — revenue recognition)
- Periods can be `open`, `closed`, or `locked`; posting to closed periods is blocked at application level

### 2. Double-Entry Accounting (Golden Rule)
Every monetary event generates a `journal_entry` with `journal_lines` where:
```
SUM(debit) = SUM(credit)   ← enforced at application service layer
```

Example: Customer Invoice Posted
```
DR  Accounts Receivable    1,100.00
    CR  Revenue                        1,000.00
    CR  VAT Payable                      100.00
```

### 3. Unified Party Model
`parties` replaces separate customers/suppliers tables.
`type` ∈ {customer, supplier, both, employee, partner, other}.
Mirrors SAP Business Partner (BP) model — eliminates data duplication.

### 4. Polymorphic References
Used throughout for loose coupling:
- `journal_entries.source_type / source_id` → any originating document
- `stock_movements.source_type / source_id` → GRN, delivery, adjustment, return
- `inventory_layers.source_type / source_id` → receipt source
- `identifiers.entity_type / entity_id` → any entity (product, batch, location…)
- `trace_logs.entity_type / entity_id` → any traced entity
- `audit_logs.auditable_type / auditable_id` → any model
- `attachments.attachable_type / attachable_id` → any entity
- `payment_allocations.invoice_type / invoice_id` → invoice or credit note

### 5. SMB Flexibility (Optional Flows)
| Feature | How |
|---------|-----|
| GRN without PO | `goods_receipts.purchase_order_id` is nullable |
| Direct sale (no SO) | `delivery_orders.sales_order_id` is nullable |
| Batch tracking optional | `batch_id` nullable on movement/balance/GRN/delivery tables |
| Serial tracking optional | `serial_id` nullable throughout |
| Return without original ref | `return_order_lines.original_line_id` nullable; batch/serial nullable |

### 6. Multi-Price Support
`product_prices` supports:
- Multiple price lists (sale, purchase, transfer)
- Tier pricing (`min_qty`)
- Time-bound prices (`valid_from`, `valid_to`)
- Per-currency, per-UoM
- Variant-level overrides

### 7. Inventory Valuation Methods
`inventory_layers` supports per-product configurable method:
- **FIFO** — first-in, first-out
- **LIFO** — last-in, first-out
- **FEFO** — first-expired, first-out (pharma/food)
- **WAC** — weighted average cost (running)
- **SPECIFIC** — specific identification (high-value serials)

### 8. AIDC / GS1 Unified Identifier
`identifiers` table is technology-agnostic:
- EAN-13, UPC-A, Code128, QR Code, Data Matrix
- RFID HF (ISO 15693), RFID UHF EPC Gen2 (ISO 18000-6C)
- NFC (ISO 14443)
- GS1 EPC (SGTIN, SSCC, GRAI, GIAI, GSRN, GDTI)
- `gs1_application_identifiers` JSON field maps GS1 AIs
- `epc_uri` field stores full EPC URI for EPCIS events

### 9. Hierarchical Structures
All trees use **materialized path** pattern (`path` column + `level` column):
- `organizations` — org unit hierarchy
- `categories` — product category tree
- `chart_of_accounts` — account hierarchy
- `cost_centers` — cost center hierarchy
- `locations` — warehouse location tree (zone→aisle→rack→shelf→bin)

This enables fast subtree queries without recursive CTEs.

---

## Deployment

```bash
# Copy migrations to module directories, then:
php artisan migrate --path=app/Modules/Core/database/migrations
php artisan migrate --path=app/Modules/Identity/database/migrations
php artisan migrate --path=app/Modules/Finance/database/migrations
php artisan migrate --path=app/Modules/Product/database/migrations
php artisan migrate --path=app/Modules/Warehouse/database/migrations
php artisan migrate --path=app/Modules/Inventory/database/migrations
php artisan migrate --path=app/Modules/Procurement/database/migrations
php artisan migrate --path=app/Modules/Sales/database/migrations
php artisan migrate --path=app/Modules/Returns/database/migrations
php artisan migrate --path=app/Modules/Traceability/database/migrations
php artisan migrate --path=app/Modules/Audit/database/migrations
php artisan migrate --path=app/Modules/Config/database/migrations

# Or run all at once using a custom MigrationServiceProvider
php artisan migrate
```

---

## Compliance & Standards Alignment

| Standard | Coverage |
|----------|---------|
| GAAP / IFRS | Period-based accrual, double-entry, CoA structure, revenue recognition |
| SAP FI/MM/SD | Journal entries, GRN 3-way match, AP/AR, cost centers, payment terms |
| Oracle Financials | Chart of accounts hierarchy, fiscal years, period control |
| MS Dynamics 365 | Party model, price lists, multi-currency, dimensions (cost centers) |
| DSCSA (Pharma) | Batch/lot traceability, serial numbers, trace_logs EPCIS events |
| GS1 / EPCIS | identifiers table: EPC URI, GS1 AIs, SGTIN, SSCC, scan_sessions |
| SOX Compliance | Immutable audit_logs, period locking, journal posting controls |
| ISO 15693 / 18000 | RFID HF/UHF identifier types in identifiers table |
