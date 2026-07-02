# AutoERP Migration and Architecture Foundation Audit

Audit date: 2026-06-28

Scope: canonical create migrations and the architecture-foundation ownership milestone recovered from the verified Item Pricing baseline. This is a source-level audit; runtime database migration success is a separate release gate.

## Verified result

- 246 migration files create 246 unique tables.
- Every migration creates exactly one table and drops the same table in `down()`.
- No migration uses `Schema::table()` patching.
- No duplicate migration timestamps or table creations remain.
- Native database enum calls: 0. Governed values use bounded portable string columns and application/domain validation.
- Explicit key names scanned: 2,010; longest identifier: 60 characters.
- Production module graph: 29 modules, 183 direct edges, 0 cyclic components.
- The generic `Extension` module and duplicate `customer_vehicles` / `supplier_vehicles` persistence were removed.
- `Idempotency` owns idempotency persistence. `PrivateObject` owns private storage capability and intentionally has no database table.

## Ownership decisions

- `Core` contains technical primitives only; it does not own password hashing, private-object storage, or idempotency persistence.
- `Vehicle` owns the authoritative `vehicle_ownerships` history. Customer and Supplier provide owner snapshot resolvers only.
- `User` is the only production writer of the tenant permission catalogue; feature modules register definitions.
- `Invoice` publishes cancellation contexts; source modules restore their own records.
- `Tax` consumes owner-provided immutable document/item/party DTOs rather than importing concrete business models.
- `Item` owns base-UOM revision commands; `Inventory` owns conversion of inventory balances, reservations, allocations, and valuation records.

## Migration inventory

### Infrastructure

Tables (5): `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### Configuration

Tables (6): `global_configuration_values`, `tenant_configuration_values`, `organization_unit_configuration_values`, `global_configuration_value_revisions`, `tenant_configuration_value_revisions`, `organization_unit_configuration_value_revisions`.

### Tenant

Tables (14): `tenant_plans`, `tenant_plan_revisions`, `tenants`, `tenant_subscriptions`, `tenant_current_subscriptions`, `tenant_documents`, `tenant_domains`, `tenant_primary_domains`, `tenant_storage_cleanup_jobs`, `tenant_event_outbox`, `tenant_subscription_events`, `tenant_lifecycle_events`, `tenant_onboarding_states`, `tenant_onboarding_steps`.

### OrganizationUnit

Tables (3): `organization_unit_types`, `organization_units`, `organization_unit_documents`.

### User

Tables (14): `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `user_permissions`, `user_organization_units`, `user_documents`, `user_devices`, `platform_operators`, `platform_permissions`, `platform_operator_permissions`, `platform_operator_invitations`, `platform_operator_invitation_deliveries`.

### Auth

Tables (17): `auth_providers`, `auth_clients`, `auth_identities`, `auth_sessions`, `auth_access_tokens`, `auth_refresh_tokens`, `auth_authorization_codes`, `auth_login_attempts`, `auth_platform_login_attempts`, `auth_registration_invitations`, `auth_processed_integration_events`, `auth_registration_invitation_deliveries`, `auth_platform_sessions`, `auth_platform_access_tokens`, `auth_platform_refresh_tokens`, `auth_user_password_credentials`, `auth_platform_operator_password_credentials`.

### Idempotency

Tables (1): `idempotency_records`.

### Sequence

Tables (1): `sequences`.

### UOM

Tables (2): `unit_of_measures`, `uom_conversions`.

### Warehouse

Tables (2): `warehouses`, `warehouse_locations`.

### Finance

Tables (14): `finance_account_types`, `finance_account_categories`, `finance_accounts`, `finance_dimensions`, `finance_posting_profiles`, `finance_posting_profile_rules`, `finance_journal_entries`, `finance_journal_lines`, `finance_ledger_entries`, `finance_account_balances`, `finance_bank_reconciliations`, `finance_bank_statement_lines`, `finance_budgets`, `finance_budget_lines`.

### Tax

Tables (9): `taxes`, `tax_rates`, `tax_groups`, `tax_group_lines`, `tax_posting_profiles`, `tax_document_snapshots`, `tax_transactions`, `customer_tax_profiles`, `supplier_tax_profiles`.

### Item

Tables (10): `item_categories`, `item_brands`, `items`, `item_units`, `item_variants`, `item_bundles`, `item_prices`, `item_codes`, `item_usage_rules`, `item_base_uom_revisions`.

### Supplier

Tables (10): `suppliers`, `supplier_contacts`, `supplier_addresses`, `supplier_bank_accounts`, `supplier_categories`, `supplier_category_assignments`, `supplier_documents`, `supplier_item_mappings`, `supplier_credit_profiles`, `supplier_status_histories`.

### Customer

Tables (9): `customers`, `customer_contacts`, `customer_addresses`, `customer_bank_accounts`, `customer_categories`, `customer_category_assignments`, `customer_documents`, `customer_credit_profiles`, `customer_status_histories`.

### Vehicle

Tables (9): `vehicle_makes`, `vehicle_types`, `vehicle_categories`, `vehicle_models`, `vehicles`, `vehicle_documents`, `vehicle_ownerships`, `vehicle_attributes`, `vehicle_status_histories`.

### Hr

Tables (16): `hr_departments`, `hr_designations`, `hr_employment_types`, `hr_skills`, `hr_certifications`, `hr_licenses`, `hr_employees`, `hr_employee_contacts`, `hr_employee_addresses`, `hr_employee_documents`, `hr_employee_skill_assignments`, `hr_employee_certification_assignments`, `hr_employee_license_assignments`, `hr_employee_rates`, `hr_employee_availabilities`, `hr_employee_status_histories`.

### Inventory

Tables (20): `inventory_batches`, `inventory_serial_numbers`, `inventory_stock_balances`, `inventory_movements`, `inventory_reservations`, `inventory_allocations`, `inventory_adjustments`, `inventory_adjustment_lines`, `inventory_transfers`, `inventory_transfer_lines`, `inventory_valuation_layers`, `inventory_allocation_lines`, `inventory_allocation_issues`, `inventory_valuation_consumptions`, `inventory_stock_state_changes`, `inventory_cost_adjustments`, `inventory_cost_adjustment_lines`, `inventory_stock_counts`, `inventory_stock_count_lines`, `inventory_number_sequences`.

### Invoice

Tables (8): `invoices`, `invoice_lines`, `invoice_sources`, `invoice_source_lines`, `invoice_adjustments`, `invoice_adjustment_allocations`, `invoice_balances`, `invoice_credit_allocations`.

### Payment

Tables (10): `payments`, `payment_methods`, `payment_lines`, `payment_allocations`, `payment_unapplied_balances`, `payment_reversals`, `payment_refunds`, `payment_status_histories`, `cheque_templates`, `cheque_print_logs`.

### Purchase

Tables (11): `purchase_orders`, `purchase_order_lines`, `purchase_header_adjustments`, `goods_receipt_notes`, `goods_receipt_note_lines`, `purchase_invoice_links`, `purchase_returns`, `purchase_return_lines`, `purchase_return_adjustment_allocations`, `purchase_debit_notes`, `purchase_adjustment_allocations`.

### Sales

Tables (15): `sales_quotations`, `sales_quotation_lines`, `sales_orders`, `sales_order_lines`, `sales_header_adjustments`, `sales_deliveries`, `sales_delivery_lines`, `sales_invoice_links`, `sales_returns`, `sales_return_lines`, `sales_return_adjustment_allocations`, `sales_credit_notes`, `sales_status_histories`, `sales_allocations`, `sales_allocation_lines`.

### VehicleRental

Tables (24): `rental_reservations`, `rental_agreements`, `rental_agreement_terms`, `rental_agreement_rate_versions`, `rental_agreement_rate_components`, `vehicle_finance_agreements`, `vehicle_finance_installments`, `vehicle_finance_status_histories`, `rental_vehicle_allocations`, `rental_driver_assignments`, `rental_vehicle_replacements`, `rental_custody_events`, `rental_custody_event_items`, `rental_usage_logs`, `rental_usage_events`, `rental_usage_contexts`, `rental_expenses`, `rental_expense_allocations`, `rental_billing_periods`, `rental_calculation_runs`, `rental_calculation_lines`, `rental_deposit_requirements`, `rental_deposit_links`, `rental_status_histories`.

### VehicleService

Tables (8): `vehicle_service_jobs`, `vehicle_service_inspections`, `vehicle_service_job_lines`, `vehicle_service_line_employees`, `vehicle_service_documents`, `vehicle_service_invoice_links`, `vehicle_service_payment_links`, `vehicle_service_status_histories`.

### Audit

Tables (1): `audit_logs`.

### ReferenceData

Tables (4): `countries`, `currencies`, `languages`, `timezones`.

## Runtime release gates

- Laravel route boot is blocked in this verification environment by the missing PHP `mbstring` extension.
- PHPUnit is blocked by missing DOM, mbstring, XML, and XMLWriter extensions.
- Database-backed migrations, concurrency tests, and tenant/organization-unit adversarial tests must run in the deployment-equivalent environment.
- The supplied frontend dependency snapshot lacks the Linux Rollup optional binary required by Vitest/Vite; no fake binary or source workaround was introduced.

## Deployment note

These are corrected create migrations for a refactor/development baseline. Do not apply the archive blindly to an existing production schema. Existing deployments require a reviewed data-migration, backfill, reconciliation, and rollback plan.
