# AutoERP Migration Audit

Audit date: 2026-06-12

Scope: `app/Modules`, module migrations, models, relationships, services, requests, resources, seeders, tests, `resources/js`, and migration history.

## Result

- 191 module migrations create 191 module-owned tables.
- Every module migration creates exactly one table.
- No module migration uses `Schema::table`.
- No duplicate migration basenames remain.
- All declared non-self FK targets are created before their referencing table.
- All 197 created tables, including Laravel infrastructure tables, are documented in `tables.md`.
- No application field was removed during this cleanup.

## Execution Order

| Prefix | Module/area | Dependency note |
| --- | --- | --- |
| `000xxx` | Configuration reference data | Countries, currencies, languages, timezones, and system configuration. |
| `010xxx` | Tenant | Depends on configuration currencies. |
| `015xxx` | Tenant configuration extension | Configuration-owned table with an FK to Tenant. |
| `020xxx` | OrganizationUnit | Depends on Tenant. |
| `030xxx` | User | Depends on Tenant and OrganizationUnit. |
| `031xxx` | Auth | Depends on User, Tenant, and OrganizationUnit. |
| `040xxx` | Sequence | Depends on Tenant and OrganizationUnit. |
| `050xxx` | UOM | Depends on Tenant and OrganizationUnit. |
| `060xxx` | Warehouse | Depends on Tenant and OrganizationUnit. |
| `070xxx` | Finance | Depends on configuration, Tenant, and OrganizationUnit. |
| `080xxx` | Tax master/posting/snapshots | Depends on Finance for posting accounts. |
| `090xxx` | Item | Depends on UOM and Tax master tables. |
| `100xxx` | Supplier | Depends on Item for supplier-item mappings. |
| `110xxx` | Customer | Depends on configuration, Tenant, and OrganizationUnit. |
| `115xxx` | Tax party extensions | Tax-owned customer/supplier profiles; intentionally run after both party modules. |
| `120xxx` | Vehicle | Depends on Customer. |
| `130xxx` | HR | Depends on configuration currencies. |
| `140xxx` | Inventory | Depends on Item, UOM, and Warehouse. |
| `150xxx` | Invoice | Depends on Inventory, Item, and UOM. |
| `160xxx` | Payment | Depends on Finance and Invoice. |
| `170xxx` | Purchase | Depends on Supplier, Inventory, Invoice, Item, and UOM. |
| `180xxx` | Sales | Depends on Customer, Inventory, Invoice, Item, and UOM. |
| `190xxx` | VehicleService | Depends on Vehicle, HR, Inventory, Invoice, and Payment. |
| `200xxx` | Audit | Runs after business modules and owns no business tables. |
| `210xxx` | Extension | Runs last and references shared business identities polymorphically. |

## Module Inventory

### Core

No business tables or migrations. Core provides shared application infrastructure only.

### Configuration

- `2026_06_12_000001_create_countries_table`
- `2026_06_12_000002_create_currencies_table`
- `2026_06_12_000003_create_languages_table`
- `2026_06_12_000004_create_timezones_table`
- `2026_06_12_000005_create_global_configuration_values_table`
- `2026_06_12_015001_create_tenant_configuration_values_table`

Tables: `countries`, `currencies`, `languages`, `timezones`, `global_configuration_values`, `tenant_configuration_values`, `organization_unit_configuration_values`.

### Tenant

Migrations: `create_tenant_plans_table`, `create_tenants_table`, `create_tenant_documents_table`, `create_tenant_domains_table`, `create_tenant_storage_cleanup_jobs_table`, `create_tenant_event_outbox_table`.

Tables: `tenant_plans`, `tenants`, `tenant_documents`, `tenant_domains`, `tenant_storage_cleanup_jobs`, `tenant_event_outbox`.

### OrganizationUnit

Migrations: `create_organization_unit_types_table`, `create_organization_units_table`, `create_organization_unit_documents_table`.

Tables: `organization_unit_types`, `organization_units`, `organization_unit_documents`.

### User

Migrations: `create_users_table`, `create_roles_table`, `create_permissions_table`, `create_role_permissions_table`, `create_user_roles_table`, `create_user_permissions_table`, `create_user_organization_units_table`, `create_user_documents_table`, `create_user_devices_table`.

Tables: `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `user_permissions`, `user_organization_units`, `user_documents`, `user_devices`.

### Auth

Migrations: `create_auth_providers_table`, `create_auth_clients_table`, `create_auth_identities_table`, `create_auth_sessions_table`, `create_auth_access_tokens_table`, `create_auth_refresh_tokens_table`, `create_auth_authorization_codes_table`, `create_auth_verification_challenges_table`, `create_auth_login_attempts_table`.

Tables use the same names as the migrations.

### Sequence

Migration/table: `create_sequences_table` / `sequences`.

### UOM

Migrations/tables: `create_unit_of_measures_table`, `create_uom_conversions_table`.

### Warehouse

Migrations/tables: `create_warehouses_table`, `create_warehouse_locations_table`.

### Finance

Migrations: `create_finance_account_types_table`, `create_finance_account_categories_table`, `create_finance_accounts_table`, `create_finance_fiscal_years_table`, `create_finance_fiscal_periods_table`, `create_finance_dimensions_table`, `create_finance_posting_profiles_table`, `create_finance_posting_profile_rules_table`, `create_finance_journal_entries_table`, `create_finance_journal_lines_table`, `create_finance_ledger_entries_table`, `create_finance_account_balances_table`, `create_finance_bank_reconciliations_table`, `create_finance_bank_statement_lines_table`, `create_finance_budgets_table`, `create_finance_budget_lines_table`.

Tables use the same names as the migrations.

### Tax

Migrations: `create_taxes_table`, `create_tax_rates_table`, `create_tax_groups_table`, `create_tax_group_lines_table`, `create_tax_posting_profiles_table`, `create_tax_document_snapshots_table`, `create_tax_transactions_table`, `create_customer_tax_profiles_table`, `create_supplier_tax_profiles_table`.

Tables use the same names as the migrations. Customer and Supplier profile tables are explicitly Tax-owned extensions.

### Item

Migrations: `create_item_categories_table`, `create_item_brands_table`, `create_items_table`, `create_item_units_table`, `create_item_variants_table`, `create_item_bundles_table`, `create_item_prices_table`, `create_item_codes_table`, `create_item_usage_rules_table`, `create_item_base_uom_revisions_table`.

Tables use the same names as the migrations.

### Supplier

Migrations: `create_suppliers_table`, `create_supplier_contacts_table`, `create_supplier_addresses_table`, `create_supplier_bank_accounts_table`, `create_supplier_categories_table`, `create_supplier_category_assignments_table`, `create_supplier_documents_table`, `create_supplier_item_mappings_table`, `create_supplier_credit_profiles_table`, `create_supplier_status_histories_table`.

Tables use the same names as the migrations.

### Customer

Migrations: `create_customers_table`, `create_customer_contacts_table`, `create_customer_addresses_table`, `create_customer_bank_accounts_table`, `create_customer_categories_table`, `create_customer_category_assignments_table`, `create_customer_documents_table`, `create_customer_credit_profiles_table`, `create_customer_status_histories_table`.

Tables use the same names as the migrations.

### Vehicle

Migrations: `create_vehicle_makes_table`, `create_vehicle_types_table`, `create_vehicle_categories_table`, `create_vehicle_models_table`, `create_vehicles_table`, `create_vehicle_documents_table`, `create_vehicle_ownerships_table`, `create_vehicle_attributes_table`, `create_vehicle_status_histories_table`.

Tables use the same names as the migrations.

### Hr

Migrations: `create_hr_departments_table`, `create_hr_designations_table`, `create_hr_employment_types_table`, `create_hr_skills_table`, `create_hr_certifications_table`, `create_hr_licenses_table`, `create_hr_employees_table`, `create_hr_employee_contacts_table`, `create_hr_employee_addresses_table`, `create_hr_employee_documents_table`, `create_hr_employee_skill_assignments_table`, `create_hr_employee_certification_assignments_table`, `create_hr_employee_license_assignments_table`, `create_hr_employee_rates_table`, `create_hr_employee_availabilities_table`, `create_hr_employee_status_histories_table`.

Tables use the same names as the migrations.

### Inventory

Migrations: `create_inventory_batches_table`, `create_inventory_serial_numbers_table`, `create_inventory_stock_balances_table`, `create_inventory_movements_table`, `create_inventory_reservations_table`, `create_inventory_allocations_table`, `create_inventory_adjustments_table`, `create_inventory_adjustment_lines_table`, `create_inventory_transfers_table`, `create_inventory_transfer_lines_table`, `create_inventory_valuation_layers_table`, `create_inventory_allocation_lines_table`, `create_inventory_allocation_issues_table`, `create_inventory_valuation_consumptions_table`, `create_inventory_stock_state_changes_table`, `create_inventory_cost_adjustments_table`, `create_inventory_cost_adjustment_lines_table`, `create_inventory_stock_counts_table`, `create_inventory_stock_count_lines_table`.

Tables use the same names as the migrations.

### Invoice

Migrations: `create_invoices_table`, `create_invoice_lines_table`, `create_invoice_sources_table`, `create_invoice_source_lines_table`, `create_invoice_adjustments_table`, `create_invoice_adjustment_allocations_table`, `create_invoice_balances_table`, `create_invoice_credit_allocations_table`.

Tables use the same names as the migrations.

### Payment

Migrations: `create_payments_table`, `create_payment_methods_table`, `create_payment_lines_table`, `create_payment_allocations_table`, `create_payment_unapplied_balances_table`, `create_payment_reversals_table`, `create_payment_refunds_table`, `create_payment_status_histories_table`, `create_cheque_templates_table`, `create_cheque_print_logs_table`.

Tables use the same names as the migrations.

### Purchase

Migrations: `create_purchase_orders_table`, `create_purchase_order_lines_table`, `create_purchase_header_adjustments_table`, `create_goods_receipt_notes_table`, `create_goods_receipt_note_lines_table`, `create_purchase_invoice_links_table`, `create_purchase_returns_table`, `create_purchase_return_lines_table`, `create_purchase_return_adjustment_allocations_table`, `create_purchase_debit_notes_table`.

Tables use the same names as the migrations.

### Sales

Migrations: `create_sales_quotations_table`, `create_sales_quotation_lines_table`, `create_sales_orders_table`, `create_sales_order_lines_table`, `create_sales_header_adjustments_table`, `create_sales_deliveries_table`, `create_sales_delivery_lines_table`, `create_sales_invoice_links_table`, `create_sales_returns_table`, `create_sales_return_lines_table`, `create_sales_return_adjustment_allocations_table`, `create_sales_credit_notes_table`, `create_sales_status_histories_table`.

Tables use the same names as the migrations.

### VehicleService

Migrations: `create_vehicle_service_jobs_table`, `create_vehicle_service_inspections_table`, `create_vehicle_service_job_lines_table`, `create_vehicle_service_line_employees_table`, `create_vehicle_service_documents_table`, `create_vehicle_service_invoice_links_table`, `create_vehicle_service_payment_links_table`, `create_vehicle_service_status_histories_table`.

Tables use the same names as the migrations.

### Reporting

No persisted tables or migrations. Reporting reads domain-owned data.

### Audit

Migration/table: `create_audit_logs_table` / `audit_logs`.

### Extension

Migrations/tables: `create_attachments_table`, `create_entity_attributes_table`, `create_comments_table`.

## Mega Migrations Split

- HR: 1 migration containing 16 tables became 16 table migrations.
- Sales: 1 migration containing 13 tables became 13 table migrations.
- VehicleService: 1 migration containing 8 tables became 8 table migrations.
- Tax master: 1 migration containing 4 tables became 4 table migrations.
- Tax profiles/posting/snapshots: 1 migration containing 5 tables became 5 table migrations.
- Finance reconciliation/budget and Inventory valuation/workflow were already table-wise after their patch consolidation.

## Patch Consolidation Review

The following patches were already safely folded into final create migrations in commit `1f543d789` and were preserved:

- Payment cheque details, cheque tables, status history, metadata, source tracing, and enterprise hardening.
- Purchase backend UOM, quantity, adjustment, return, source, approval, and audit fields.
- Finance fiscal, posting profile, ledger traceability, reconciliation, budget, and dimension hardening.
- Inventory allocation, valuation consumption, state workflow, transfer, cost adjustment, and stock count hardening.
- Item SKU/barcode/code/unit uniqueness.
- Supplier bank account and supplier-item uniqueness.
- Customer bank account uniqueness.
- User username and tenant-scoped username uniqueness.

The Tax item override patch was consolidated during this audit. `default_tax_group_id`, `purchase_tax_group_id`, `sales_tax_group_id`, and `is_tax_exempt` now live directly in the Item-owned `items` create migration.

## Fields

- Removed: none.
- Retained: all fields referenced by models, casts, services, requests, resources, seeders, tests, or frontend payloads.
- Intentional metadata JSON columns were retained where services use flexible source, audit, layout, or extension data.

## Constraints And Indexes

- Existing code/number uniqueness constraints were preserved.
- Existing frequent-filter and source-trace indexes were preserved.
- Existing cascade, restrict, and null-on-delete rules were preserved.
- Business money, quantity, rate, and percentage columns remain `decimal(20,6)`.
- Cheque layout coordinates retain smaller physical-layout precision by design.
- Child tables that omit direct tenant/org columns inherit scope through a mandatory parent relationship.

## Remaining Risks

- Composite unique constraints containing nullable `organization_unit_id` follow database NULL semantics. They may allow more than one global-scope row with otherwise identical values. Changing this requires an explicit business rule and was not inferred during migration cleanup.
- Several actor/source IDs are intentionally unsigned IDs or polymorphic pairs rather than hard FKs to avoid cross-module coupling and preserve historical records.
- Migration timestamp consolidation is appropriate for the current development/refactor stage but is not suitable for an already deployed production database without a release-specific migration strategy.

- `2026_06_12_020006_create_organization_unit_configuration_values_table`
