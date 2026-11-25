# AutoERP Migration and Architecture Foundation Audit

Audit date: 2026-11-26

Scope: canonical create migrations and the architecture-foundation ownership milestone recovered from the verified Item Pricing baseline. This is a source-level audit; runtime database migration success is a separate release gate.

## Verified result

- 307 migration files create 307 unique tables (302 modular migrations across 28 database-owning modules + 5 infrastructure migrations; 32 total backend modules, 33 frontend modules).
- Every migration creates exactly one table and drops the same table in `down()`.
- No migration uses `Schema::table()` patching.
- No duplicate migration timestamps or table creations remain.
- Native database enum calls: 0. Governed values use bounded portable string columns and application/domain validation.
- Explicit key names scanned: 2,010; longest identifier: 60 characters.
- Production module graph: 32 backend modules (28 database-owning modules), 0 cyclic components.
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

Tables (4): `organization_unit_types`, `organization_units`, `organization_unit_documents`, `organization_unit_legal_profiles`.

### User

Tables (13): `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `user_permissions`, `user_organization_units`, `user_documents`, `user_devices`, `platform_operators`, `platform_permissions`, `platform_operator_permissions`, `user_quick_access_settings`.

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

### Expense

Tables (2): `expense_types`, `expenses`.

### Finance

Tables (18): `finance_account_types`, `finance_account_categories`, `finance_accounts`, `finance_dimensions`, `finance_posting_profiles`, `finance_journal_entries`, `finance_journal_lines`, `finance_ledger_entries`, `finance_account_balances`, `finance_bank_reconciliations`, `finance_bank_statement_lines`, `finance_budgets`, `finance_budget_lines`, `finance_account_roles`, `finance_account_assignments`, `finance_posting_profile_rules`, `finance_accounting_periods`, `finance_accounting_period_events`.

### Tax

Tables (9): `taxes`, `tax_rates`, `tax_groups`, `tax_group_lines`, `tax_posting_profiles`, `tax_document_snapshots`, `tax_transactions`, `customer_tax_profiles`, `supplier_tax_profiles`.

### Item

Tables (10): `item_categories`, `item_brands`, `items`, `item_units`, `item_variants`, `item_bundles`, `item_prices`, `item_codes`, `item_usage_rules`, `item_base_uom_revisions`.

### Supplier

Tables (11): `suppliers`, `supplier_contacts`, `supplier_addresses`, `supplier_bank_accounts`, `supplier_categories`, `supplier_category_assignments`, `supplier_documents`, `supplier_item_mappings`, `supplier_credit_profiles`, `supplier_status_histories`, `supplier_whatsapp_verifications`.

### Customer

Tables (10): `customers`, `customer_contacts`, `customer_addresses`, `customer_bank_accounts`, `customer_categories`, `customer_category_assignments`, `customer_documents`, `customer_credit_profiles`, `customer_status_histories`, `customer_whatsapp_verifications`.

### Vehicle

Tables (9): `vehicle_makes`, `vehicle_types`, `vehicle_categories`, `vehicle_models`, `vehicles`, `vehicle_documents`, `vehicle_ownerships`, `vehicle_attributes`, `vehicle_status_histories`.

### Hr

Tables (42): `hr_departments`, `hr_designations`, `hr_employment_types`, `hr_skills`, `hr_certifications`, `hr_licenses`, `hr_employees`, `hr_employee_contacts`, `hr_employee_addresses`, `hr_employee_documents`, `hr_employee_skill_assignments`, `hr_employee_certification_assignments`, `hr_employee_license_assignments`, `hr_employee_rates`, `hr_employee_availabilities`, `hr_employee_status_histories`, `hr_shifts`, `hr_shift_breaks`, `hr_shift_assignments`, `hr_shift_swaps`, `hr_shift_reallocations`, `hr_holiday_calendars`, `hr_holidays`, `hr_attendance_records`, `hr_attendance_punches`, `hr_attendance_regularizations`, `hr_attendance_adjustments`, `hr_leave_types`, `hr_leave_policies`, `hr_leave_balances`, `hr_leave_requests`, `hr_comp_off_claims`, `hr_shift_rotations`, `hr_shift_rotation_steps`, `hr_shift_rotation_assignments`, `hr_biometric_devices`, `hr_biometric_device_users`, `hr_biometric_raw_punches`, `hr_attendance_geofences`, `hr_attendance_kiosks`, `hr_employee_attendance_credentials`, `hr_attendance_batch_imports`.

### Inventory

Tables (21): `inventory_batches`, `inventory_serial_numbers`, `inventory_stock_balances`, `inventory_movements`, `inventory_reservations`, `inventory_allocations`, `inventory_adjustments`, `inventory_adjustment_lines`, `inventory_transfers`, `inventory_transfer_lines`, `inventory_valuation_layers`, `inventory_allocation_lines`, `inventory_allocation_issues`, `inventory_valuation_consumptions`, `inventory_stock_state_changes`, `inventory_cost_adjustments`, `inventory_cost_adjustment_lines`, `inventory_stock_counts`, `inventory_stock_count_lines`, `inventory_number_sequences`, `inventory_batch_price_revisions`.

### Invoice

Tables (11): `invoice_layouts`, `invoices`, `invoice_lines`, `invoice_sources`, `invoice_source_lines`, `invoice_adjustments`, `invoice_adjustment_allocations`, `invoice_balances`, `invoice_credit_allocations`, `invoice_posting_plans`, `invoice_document_snapshots`.

### Payment

Tables (10): `payments`, `payment_methods`, `payment_lines`, `payment_allocations`, `payment_unapplied_balances`, `payment_reversals`, `payment_refunds`, `payment_lifecycle_events`, `cheque_templates`, `cheque_print_logs`.

### Purchase

Tables (12): `purchase_orders`, `purchase_order_lines`, `purchase_header_adjustments`, `goods_receipt_notes`, `goods_receipt_note_lines`, `purchase_invoice_links`, `purchase_returns`, `purchase_return_lines`, `purchase_return_adjustment_allocations`, `purchase_debit_notes`, `purchase_adjustment_allocations`, `goods_receipt_note_line_batch_allocations`.

### Selling

Tables (4): `sales`, `sale_lines`, `sale_returns`, `sale_return_lines`.

### Promotion

Tables (10): `promotion_offers`, `promotion_card_templates`, `promotion_card_benefits`, `promotion_cards`, `promotion_offer_assignments`, `promotion_job_line_choices`, `promotion_qualification_events`, `promotion_redemption_events`, `promotion_card_lifecycle_events`, `promotion_offer_lifecycle_events`.

### VehicleRental

Tables (28): `vehicle_rental_customer_agreements`, `vehicle_rental_customer_agreements_history`, `vehicle_rental_owner_agreements`, `vehicle_rental_owner_agreements_history`, `vehicle_rental_uses`, `vehicle_rental_use_history`, `vehicle_rental_running_charts`, `vehicle_rental_running_chart_history`, `vehicle_rental_customer_base_charges`, `vehicle_rental_owner_base_charges`, `vehicle_rental_customer_usage_charges`, `vehicle_rental_owner_usage_charges`, `vehicle_rental_incidents`, `vehicle_rental_incident_events`, `vehicle_rental_incident_attachments`, `vehicle_rental_reservations`, `vehicle_rental_reservation_histories`, `vehicle_rental_finance_agreements`, `vehicle_rental_finance_installments`, `vehicle_rental_finance_status_histories`, `vehicle_rental_expenses`, `vehicle_rental_expense_allocations`, `vehicle_rental_agreement_adjustments`, `vehicle_rental_agreement_adjustments_history`, `vehicle_rental_billing_cycles`, `vehicle_rental_billing_cycle_charts`, `vehicle_rental_billing_cycle_items`, `vehicle_rental_reassignments`.

### VehicleService

Tables (16): `vehicle_service_job_types`, `vehicle_service_jobs`, `vehicle_service_inspections`, `vehicle_service_job_lines`, `vehicle_service_line_employees`, `vehicle_service_documents`, `vehicle_service_invoice_links`, `vehicle_service_payment_links`, `vehicle_service_status_histories`, `vehicle_service_supervisor_commission_policies`, `vehicle_service_labor_item_commission_rules`, `vehicle_service_job_discounts`, `vehicle_service_legacy_import_batches`, `vehicle_service_legacy_histories`, `vehicle_service_legacy_history_items`, `vehicle_service_job_feedbacks`.

### Payroll

Tables (14): `payroll_salary_components`, `payroll_salary_structures`, `payroll_salary_structure_items`, `payroll_employee_salary_assignments`, `payroll_tax_tables`, `payroll_tax_brackets`, `payroll_statutory_rules`, `payroll_pay_periods`, `payroll_pay_runs`, `payroll_payslips`, `payroll_payslip_items`, `payroll_adjustments`, `payroll_employee_loans`, `payroll_loan_repayments`.

### Audit

Tables (1): `audit_logs`.

### ReferenceData

Tables (4): `countries`, `currencies`, `languages`, `timezones`.

## Runtime release gates

- **PHPUnit Test Suite:** 1,433 tests passed (15,106 assertions, 0 failures) across all 32 modules on SQLite in-memory and MySQL integration profiles.
- **Migration Architecture Integrity:** `ModuleMigrationBaselineTest` and `HistoricalRetentionMigrationTest` 100% compliant (302 modular migrations, 5 root migrations, 307 total, 0 `Schema::table` patches, `restrictOnDelete` enforced on all status history tables).
- **Frontend Test Suite:** 177 Vitest test files (745 tests passed, 0 failures) with React 19 testing library (Vitest 5.x).
- **TypeScript Static Typing:** 0 errors across 922+ source files in strict compilation mode (`tsc --noEmit`).
- **Code Style & Formatting:** 0 Pint violations (`php vendor/bin/pint --test`) and 0 ESLint errors.

## Deployment note

These are corrected create migrations for a refactor/development baseline. Do not apply the archive blindly to an existing production schema. Existing deployments require a reviewed data-migration, backfill, reconciliation, and rollback plan.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
