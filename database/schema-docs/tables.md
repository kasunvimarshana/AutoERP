# AutoERP Table Catalog

This catalog documents the complete schema produced by the 307 canonical create migrations (5 root + 302 modular) across all 32 backend modules (paired with 33 frontend modules) and infrastructure. The enclosing section is the owning module for every listed table. The ownership catalogue reflects the fully verified baseline migrations with zero `Schema::table()` patches and strict single-table fresh-baseline compliance.

Conventions:

- Business money, quantity, rate, and percentage values use `decimal(20,6)` unless a physical-layout precision is explicitly justified.
- `tenant_id` identifies tenant ownership; nullable `organization_unit_id` adds operational scope where the domain requires it.
- Polymorphic `source_type`/`source_id` pairs preserve source traceability without cross-module database coupling.
- Database comments are supplementary only; this catalog is the portable source of table documentation.
- Sections describe table ownership, not exact migration execution order. Cross-module foreign keys do not transfer business ownership.

## Infrastructure

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `cache` | Stores cache records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `cache_locks` | Stores cache lock records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `jobs` | Stores job records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `job_batches` | Stores job batch records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `failed_jobs` | Stores failed job records used by the owning module. | No declared foreign keys. | unique `failed_jobs_uuid_uk` |

## Core

No business tables. Core contains technical primitives, concurrency abstractions, exact decimal/time support, and domain-safe exceptions only.

## Idempotency

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `idempotency_records` | Owns replay-safe command/request result records for cross-module idempotent operations. | No declared foreign keys. | tenant scoped; organization-unit aware |

## PrivateObject

No database tables. PrivateObject owns the private-storage capability and canonical object-key operations. Feature modules own their document metadata and lifecycle records.

## Tenant

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `tenant_plans` | Stores SaaS subscription-plan definitions. | No declared foreign keys. | unique `tenant_plans_slug_uk`; optimistic concurrency |
| `tenant_plan_revisions` | Tracks historical parameter revisions and audit lineage for Tenant Plan. | No declared foreign keys. | optimistic concurrency |
| `tenants` | Stores tenant identity, authoritative lifecycle, plan assignment, and base-accounting-currency invariant. | No declared foreign keys. | unique `tenants_uuid_uk`; unique `tenants_code_uk`; unique `tenants_slug_uk`; optimistic concurrency |
| `tenant_subscriptions` | Governs business data, relationships, and lifecycle records for Tenant Subscriptions within the Tenant module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `tenant_current_subscriptions` | Governs business data, relationships, and lifecycle records for Tenant Current Subscriptions within the Tenant module. | No declared foreign keys. | unique `tenant_current_subscriptions_subscription_uk`; tenant scoped; optimistic concurrency |
| `tenant_documents` | Stores private tenant-owned file metadata and checksums. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `tenant_domains` | Stores tenant hostnames and DNS ownership-verification state. | No declared foreign keys. | unique `tenant_domains_domain_uk`; tenant scoped; optimistic concurrency |
| `tenant_primary_domains` | Governs business data, relationships, and lifecycle records for Tenant Primary Domains within the Tenant module. | No declared foreign keys. | unique `tenant_primary_domains_domain_uk`; tenant scoped; optimistic concurrency |
| `tenant_storage_cleanup_jobs` | Governs business data, relationships, and lifecycle records for Tenant Storage Cleanup Jobs within the Tenant module. | No declared foreign keys. | tenant scoped |
| `tenant_event_outbox` | Governs business data, relationships, and lifecycle records for Tenant Event Outbox within the Tenant module. | No declared foreign keys. | unique `tenant_event_outbox_uuid_uk`; tenant scoped |
| `tenant_subscription_events` | Records domain lifecycle events and audit trail for Tenant Subscription. | No declared foreign keys. | tenant scoped |
| `tenant_lifecycle_events` | Records domain lifecycle events and audit trail for Tenant Lifecycle. | No declared foreign keys. | tenant scoped |
| `tenant_onboarding_states` | Governs business data, relationships, and lifecycle records for Tenant Onboarding States within the Tenant module. | No declared foreign keys. | unique `tenant_onboarding_operation_uk`; tenant scoped; organization-unit aware; optimistic concurrency |
| `tenant_onboarding_steps` | Governs business data, relationships, and lifecycle records for Tenant Onboarding Steps within the Tenant module. | No declared foreign keys. | tenant scoped |

## OrganizationUnit

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `organization_unit_types` | Stores reusable type definitions for organization unit type. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `organization_units` | Stores the tenant organization hierarchy used for operational scoping. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `organization_unit_documents` | Stores document metadata associated with organization unit document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `organization_unit_legal_profiles` | Governs business data, relationships, and lifecycle records for Organization Unit Legal Profiles within the OrganizationUnit module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |

## User

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `users` | Stores tenant-owned user identities and separate tenant-less SaaS platform-operator identities. | No declared foreign keys. | tenant scoped; soft deletes; optimistic concurrency |
| `roles` | Stores role records used by the owning module. | No declared foreign keys. | tenant scoped; soft deletes; optimistic concurrency |
| `permissions` | Stores permission records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `role_permissions` | Stores role permission records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `user_roles` | Stores user role records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `user_permissions` | Stores user permission records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `user_organization_units` | Stores explicit organization-unit access assignments for tenant-owned users. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `user_documents` | Stores document metadata associated with user document. | No declared foreign keys. | tenant scoped; soft deletes; optimistic concurrency |
| `user_devices` | Stores user device records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `platform_operators` | Governs business data, relationships, and lifecycle records for Platform Operators within the User module. | No declared foreign keys. | unique `platform_operators_email_uk`; optimistic concurrency |
| `platform_permissions` | Governs business data, relationships, and lifecycle records for Platform Permissions within the User module. | No declared foreign keys. | unique `platform_permissions_name_uk` |
| `platform_operator_permissions` | Governs business data, relationships, and lifecycle records for Platform Operator Permissions within the User module. | No declared foreign keys. | Primary key and domain query indexes. |
| `user_quick_access_settings` | Stores personalized quick access navigation shortcuts and display ordering per user. | No declared foreign keys. | tenant scoped; optimistic concurrency |

## Auth

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `auth_providers` | Stores auth provider records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_clients` | Stores auth client records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_identities` | Stores auth identity records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_sessions` | Stores authenticated session state for auth session. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `auth_access_tokens` | Stores revocable authentication tokens for auth access token. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_refresh_tokens` | Stores revocable authentication tokens for auth refresh token. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_authorization_codes` | Stores auth authorization code records used by the owning module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_login_attempts` | Records security attempts for monitoring and throttling. | No declared foreign keys. | tenant scoped |
| `auth_platform_login_attempts` | Governs business data, relationships, and lifecycle records for Auth Platform Login Attempts within the Auth module. | No declared foreign keys. | Primary key and domain query indexes. |
| `auth_registration_invitations` | Governs business data, relationships, and lifecycle records for Auth Registration Invitations within the Auth module. | No declared foreign keys. | unique `auth_registration_invites_public_uk`; unique `auth_registration_invites_token_uk`; tenant scoped; organization-unit aware; optimistic concurrency |
| `auth_processed_integration_events` | Records domain lifecycle events and audit trail for Auth Processed Integration. | No declared foreign keys. | tenant scoped |
| `auth_registration_invitation_deliveries` | Governs business data, relationships, and lifecycle records for Auth Registration Invitation Deliveries within the Auth module. | No declared foreign keys. | unique `auth_reg_invite_deliveries_public_uk`; tenant scoped; optimistic concurrency |
| `auth_platform_sessions` | Governs business data, relationships, and lifecycle records for Auth Platform Sessions within the Auth module. | No declared foreign keys. | optimistic concurrency |
| `auth_platform_access_tokens` | Governs business data, relationships, and lifecycle records for Auth Platform Access Tokens within the Auth module. | No declared foreign keys. | optimistic concurrency |
| `auth_platform_refresh_tokens` | Governs business data, relationships, and lifecycle records for Auth Platform Refresh Tokens within the Auth module. | No declared foreign keys. | optimistic concurrency |
| `auth_user_password_credentials` | Governs business data, relationships, and lifecycle records for Auth User Password Credentials within the Auth module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `auth_platform_operator_password_credentials` | Governs business data, relationships, and lifecycle records for Auth Platform Operator Password Credentials within the Auth module. | No declared foreign keys. | optimistic concurrency |

## Configuration

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `global_configuration_values` | Registered global configuration overrides. | No declared foreign keys. | unique `global_configuration_values_key_uk`; optimistic concurrency |
| `tenant_configuration_values` | Registered tenant configuration overrides. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `organization_unit_configuration_values` | Registered organization-unit configuration overrides. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `global_configuration_value_revisions` | Tracks historical parameter revisions and audit lineage for Global Configuration Value. | No declared foreign keys. | optimistic concurrency |
| `tenant_configuration_value_revisions` | Tracks historical parameter revisions and audit lineage for Tenant Configuration Value. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `organization_unit_configuration_value_revisions` | Tracks historical parameter revisions and audit lineage for Organization Unit Configuration Value. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |

## ReferenceData

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `countries` | Provides reference country codes and names used by addresses and localization. | No declared foreign keys. | unique `countries_code_uk`; optimistic concurrency |
| `currencies` | Provides currency definitions and precision used by financial documents. | No declared foreign keys. | unique `currencies_code_uk`; optimistic concurrency |
| `languages` | Stores language records used by the owning module. | No declared foreign keys. | unique `languages_code_uk`; optimistic concurrency |
| `timezones` | Stores timezone records used by the owning module. | No declared foreign keys. | unique `timezones_name_uk`; optimistic concurrency |

## Sequence

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `sequences` | Generates tenant- and scope-specific business document numbers without hardcoded IDs. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |

## UOM

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `unit_of_measures` | Stores generic tenant UOM definitions without module-specific usage flags. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `uom_conversions` | Stores uom conversion records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |

## Warehouse

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `warehouses` | Stores tenant warehouses and their organization-unit ownership. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `warehouse_locations` | Stores warehouse location records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |

## Expense

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `expense_types` | Stores tenant-wide, user-managed expense classifications reused by every branch. | No declared foreign keys. | tenant scoped; soft deletes; optimistic concurrency |
| `expenses` | Stores immutable branch-paid expense source documents and Cash/Bank posting traceability. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |

## Finance

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `finance_account_types` | Stores reusable type definitions for finance account type. | No declared foreign keys. | tenant scoped |
| `finance_account_categories` | Stores reusable classifications for finance account category. | No declared foreign keys. | tenant scoped |
| `finance_accounts` | Stores the chart of accounts used for posting, balances, budgets, and bank accounts. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `finance_dimensions` | Stores finance dimension records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_posting_profiles` | Stores finance posting profile records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `finance_journal_entries` | Stores balanced accounting journal headers and posting traceability. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `finance_journal_lines` | Stores line-level detail and traceability for finance journal line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_ledger_entries` | Stores immutable posted transaction-currency debit/credit evidence plus frozen-rate base-currency debit/credit and the rebuildable base-currency running balance by account and dimension. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_account_balances` | Stores current balances for finance accounts. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_bank_reconciliations` | Stores finance bank reconciliation records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_bank_statement_lines` | Stores line-level detail and traceability for finance bank statement line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_budgets` | Stores finance budget records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_budget_lines` | Stores line-level detail and traceability for finance budget line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_account_roles` | Governs business data, relationships, and lifecycle records for Finance Account Roles within the Finance module. | No declared foreign keys. | tenant scoped |
| `finance_account_assignments` | Governs business data, relationships, and lifecycle records for Finance Account Assignments within the Finance module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `finance_posting_profile_rules` | Stores finance posting profile rule records used by the owning module. | No declared foreign keys. | tenant scoped |
| `finance_accounting_periods` | Governs business data, relationships, and lifecycle records for Finance Accounting Periods within the Finance module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `finance_accounting_period_events` | Records domain lifecycle events and audit trail for Finance Accounting Period. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Customer

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `customers` | Stores customer master data and tenant-scoped customer identifiers. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `customer_contacts` | Stores contact persons and communication details for customer contact. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `customer_addresses` | Stores address records and address roles for customer address. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `customer_bank_accounts` | Stores bank account details for customer bank account. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `customer_categories` | Stores reusable classifications for customer category. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `customer_category_assignments` | Maps customer category assignment records while preventing duplicate assignments. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `customer_documents` | Stores document metadata associated with customer document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `customer_credit_profiles` | Stores credit terms, limits, and controls for customer credit profile. | No declared foreign keys. | unique `customer_credit_profiles_cust_uk`; tenant scoped; organization-unit aware; optimistic concurrency |
| `customer_status_histories` | Records status transitions and audit context for customer status history. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `customer_whatsapp_verifications` | Stores verification code challenges, validation timestamps, and employee confirmations for Customer Whatsapp Verifications. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Supplier

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `suppliers` | Stores supplier master data and tenant-scoped supplier identifiers. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `supplier_contacts` | Stores contact persons and communication details for supplier contact. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_addresses` | Stores address records and address roles for supplier address. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_bank_accounts` | Stores bank account details for supplier bank account. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_categories` | Stores reusable classifications for supplier category. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_category_assignments` | Maps supplier category assignment records while preventing duplicate assignments. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `supplier_documents` | Stores document metadata associated with supplier document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_item_mappings` | Stores supplier item mapping records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `supplier_credit_profiles` | Stores credit terms, limits, and controls for supplier credit profile. | No declared foreign keys. | unique `supplier_credit_profiles_supp_uk`; tenant scoped; organization-unit aware |
| `supplier_status_histories` | Records status transitions and audit context for supplier status history. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `supplier_whatsapp_verifications` | Stores verification code challenges, validation timestamps, and employee confirmations for Supplier Whatsapp Verifications. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Item

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `item_categories` | Stores reusable classifications for item category. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `item_brands` | Stores item brand records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `items` | Stores the tenant item master, base UOM, identifiers, and operational settings. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `item_units` | Stores item unit records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `item_variants` | Stores item variant records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `item_bundles` | Stores item bundle records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `item_prices` | Stores item price records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `item_codes` | Stores item code records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `item_usage_rules` | Stores item usage rule records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `item_base_uom_revisions` | Records controlled revisions and change context for item base uom revision. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |

## Inventory

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `inventory_batches` | Stores inventory batch records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `inventory_serial_numbers` | Stores inventory serial number records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `inventory_stock_balances` | Stores current quantity buckets by item, warehouse, location, batch, and scope. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_movements` | Stores traceable stock state and location movements from business documents. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `inventory_reservations` | Stores inventory reservation records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `inventory_allocations` | Stores controlled allocations between related inventory allocation records. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `inventory_adjustments` | Stores inventory adjustment records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `inventory_adjustment_lines` | Stores line-level detail and traceability for inventory adjustment line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `inventory_transfers` | Stores inventory transfer records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `inventory_transfer_lines` | Stores line-level detail and traceability for inventory transfer line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `inventory_valuation_layers` | Stores inventory cost layers used to value and consume stock. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_allocation_lines` | Stores line-level detail and traceability for inventory allocation line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_allocation_issues` | Stores inventory allocation issue records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_valuation_consumptions` | Stores inventory valuation consumption records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_stock_state_changes` | Stores inventory stock state change records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_cost_adjustments` | Stores inventory cost adjustment records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `inventory_cost_adjustment_lines` | Stores line-level detail and traceability for inventory cost adjustment line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `inventory_stock_counts` | Stores inventory stock count records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `inventory_stock_count_lines` | Stores line-level detail and traceability for inventory stock count line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `inventory_number_sequences` | Governs business data, relationships, and lifecycle records for Inventory Number Sequences within the Inventory module. | No declared foreign keys. | tenant scoped |
| `inventory_batch_price_revisions` | Tracks historical parameter revisions and audit lineage for Inventory Batch Price. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |

## Purchase

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `purchase_orders` | Stores supplier purchase order headers, totals, approvals, and lifecycle state. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `purchase_order_lines` | Stores line-level detail and traceability for purchase order line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `purchase_header_adjustments` | Stores purchase header adjustment records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `goods_receipt_notes` | Stores goods receipt note records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `goods_receipt_note_lines` | Stores line-level detail and traceability for goods receipt note line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `purchase_invoice_links` | Stores traceable links between purchase invoice link and downstream documents. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `purchase_returns` | Stores purchase return records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `purchase_return_lines` | Stores line-level detail and traceability for purchase return line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `purchase_return_adjustment_allocations` | Stores controlled allocations between related purchase return adjustment allocation records. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `purchase_debit_notes` | Stores purchase debit note records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `purchase_adjustment_allocations` | Maintains settlement and payment/inventory allocation records for Purchase Adjustment. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `goods_receipt_note_line_batch_allocations` | Maintains settlement and payment/inventory allocation records for Goods Receipt Note Line Batch. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Selling

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `sales` | Governs business data, relationships, and lifecycle records for Sales within the Selling module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `sale_lines` | Stores individual itemized line entries for Sale. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `sale_returns` | Governs business data, relationships, and lifecycle records for Sale Returns within the Selling module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `sale_return_lines` | Stores individual itemized line entries for Sale Return. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Invoice

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `invoice_layouts` | Stores multi-format invoice print layout templates, typography, and company presentation configurations. | No declared foreign keys. | tenant scoped; soft deletes; optimistic concurrency |
| `invoices` | Stores receivable and payable invoice headers independent of source documents. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `invoice_lines` | Stores line-level detail and traceability for invoice line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_sources` | Stores invoice source records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_source_lines` | Stores line-level detail and traceability for invoice source line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_adjustments` | Stores invoice adjustment records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_adjustment_allocations` | Stores controlled allocations between related invoice adjustment allocation records. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_balances` | Stores current or period balances for invoice balance. | No declared foreign keys. | unique `invoice_balances_invoice_uk`; tenant scoped; organization-unit aware |
| `invoice_credit_allocations` | Stores controlled allocations between related invoice credit allocation records. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `invoice_posting_plans` | Governs business data, relationships, and lifecycle records for Invoice Posting Plans within the Invoice module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `invoice_document_snapshots` | Governs business data, relationships, and lifecycle records for Invoice Document Snapshots within the Invoice module. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Payment

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `payments` | Stores inbound and outbound payment headers, source traceability, and cheque details. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `payment_methods` | Stores payment method records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `payment_lines` | Stores line-level detail and traceability for payment line. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payment_allocations` | Stores controlled allocations between related payment allocation records. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payment_unapplied_balances` | Stores current or period balances for payment unapplied balance. | No declared foreign keys. | unique `payment_unapplied_balances_payment_uk`; tenant scoped; organization-unit aware; optimistic concurrency |
| `payment_reversals` | Stores payment reversal records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `payment_refunds` | Stores payment refund records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `payment_lifecycle_events` | Records domain lifecycle events and audit trail for Payment Lifecycle. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `cheque_templates` | Stores cheque template records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `cheque_print_logs` | Records operational events for cheque print log. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Promotion

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `promotion_offers` | Governs business data, relationships, and lifecycle records for Promotion Offers within the Promotion module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `promotion_card_templates` | Governs business data, relationships, and lifecycle records for Promotion Card Templates within the Promotion module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `promotion_card_benefits` | Governs business data, relationships, and lifecycle records for Promotion Card Benefits within the Promotion module. | No declared foreign keys. | tenant scoped |
| `promotion_cards` | Governs business data, relationships, and lifecycle records for Promotion Cards within the Promotion module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `promotion_offer_assignments` | Governs business data, relationships, and lifecycle records for Promotion Offer Assignments within the Promotion module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `promotion_job_line_choices` | Governs business data, relationships, and lifecycle records for Promotion Job Line Choices within the Promotion module. | No declared foreign keys. | tenant scoped; optimistic concurrency |
| `promotion_qualification_events` | Records domain lifecycle events and audit trail for Promotion Qualification. | No declared foreign keys. | tenant scoped |
| `promotion_redemption_events` | Records domain lifecycle events and audit trail for Promotion Redemption. | No declared foreign keys. | tenant scoped |
| `promotion_card_lifecycle_events` | Records domain lifecycle events and audit trail for Promotion Card Lifecycle. | No declared foreign keys. | tenant scoped |
| `promotion_offer_lifecycle_events` | Records domain lifecycle events and audit trail for Promotion Offer Lifecycle. | No declared foreign keys. | tenant scoped |

## Tax

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `taxes` | Stores tenant tax definitions and calculation behavior. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `tax_rates` | Stores effective rates used by tax rate. | No declared foreign keys. | tenant scoped |
| `tax_groups` | Stores tax group records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `tax_group_lines` | Stores line-level detail and traceability for tax group line. | No declared foreign keys. | tenant scoped |
| `tax_posting_profiles` | Stores tax posting profile records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `tax_document_snapshots` | Stores immutable calculation snapshots for tax document snapshot. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `tax_transactions` | Stores tax transaction records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `customer_tax_profiles` | Stores tax registration and default tax behavior for customer tax profile. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `supplier_tax_profiles` | Stores tax registration and default tax behavior for supplier tax profile. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Hr

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `hr_departments` | Stores hr department records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_designations` | Stores hr designation records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_employment_types` | Stores reusable type definitions for hr employment type. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_skills` | Stores hr skill records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_certifications` | Stores hr certification records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_licenses` | Stores hr license records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_employees` | Stores hr employee records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `hr_employee_contacts` | Stores contact persons and communication details for hr employee contact. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_addresses` | Stores address records and address roles for hr employee address. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_documents` | Stores document metadata associated with hr employee document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_skill_assignments` | Maps hr employee skill assignment records while preventing duplicate assignments. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_employee_certification_assignments` | Maps hr employee certification assignment records while preventing duplicate assignments. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_employee_license_assignments` | Maps hr employee license assignment records while preventing duplicate assignments. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_employee_rates` | Stores effective rates used by hr employee rate. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_employee_availabilities` | Stores hr employee availability records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_employee_status_histories` | Records status transitions and audit context for hr employee status history. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_shifts` | Stores shift definitions, working hours, grace windows, and break policies. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_shift_breaks` | Stores scheduled break windows belonging to an HR shift. | `shift_id` -> `hr_shifts.id` | tenant scoped; organization-unit aware |
| `hr_shift_assignments` | Stores recurring, date-range, and specific-date employee shift assignments. | `employee_id` -> `hr_employees.id`; `shift_id` -> `hr_shifts.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_shift_swaps` | Tracks peer-to-peer and manager-approved shift swap requests. | `requester_employee_id`, `target_employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_shift_reallocations` | Tracks cover assignments when an employee is absent for a scheduled shift. | `absent_employee_id`, `cover_employee_id` -> `hr_employees.id`; `shift_id` -> `hr_shifts.id` | tenant scoped; organization-unit aware |
| `hr_holiday_calendars` | Stores yearly public holiday calendar headers per tenant or branch. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `hr_holidays` | Stores individual public and company holidays under a holiday calendar. | `holiday_calendar_id` -> `hr_holiday_calendars.id` | tenant scoped; organization-unit aware |
| `hr_attendance_records` | Stores daily calculated attendance records, worked/overtime minutes, and anomaly flags. | `employee_id` -> `hr_employees.id`; `shift_id` -> `hr_shifts.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_attendance_punches` | Stores immutable clock-in/out/break punch events across all capture channels. | `employee_id` -> `hr_employees.id`; `attendance_record_id` -> `hr_attendance_records.id` | tenant scoped; organization-unit aware |
| `hr_attendance_regularizations` | Stores employee attendance regularization requests and approval workflow state. | `employee_id` -> `hr_employees.id`; `attendance_record_id` -> `hr_attendance_records.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_attendance_adjustments` | Stores immutable administrative attendance adjustment audit records. | `attendance_record_id` -> `hr_attendance_records.id` | tenant scoped; organization-unit aware |
| `hr_leave_types` | Stores leave type definitions, attachment requirements, and sandwich-rule flags. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_leave_policies` | Stores leave accrual, carry-forward, and eligibility policies per leave type. | `leave_type_id` -> `hr_leave_types.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_leave_balances` | Stores annual employee leave balances (entitled, carried forward, accrued, taken, pending). | `employee_id` -> `hr_employees.id`; `leave_type_id` -> `hr_leave_types.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_leave_requests` | Stores employee leave applications, approval state, and cancellation metadata. | `employee_id` -> `hr_employees.id`; `leave_type_id` -> `hr_leave_types.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_comp_off_claims` | Stores compensatory-off claims earned from holiday or rest-day work. | `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_shift_rotations` | Stores multi-day cyclic shift rotation pattern headers. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_shift_rotation_steps` | Stores ordered day-sequence shift steps within a shift rotation pattern. | `shift_rotation_id` -> `hr_shift_rotations.id`; `shift_id` -> `hr_shifts.id` | tenant scoped; organization-unit aware |
| `hr_shift_rotation_assignments` | Assigns employees to cyclic shift rotations with anchor dates. | `employee_id` -> `hr_employees.id`; `shift_rotation_id` -> `hr_shift_rotations.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_biometric_devices` | Stores biometric hardware device configurations, protocols, and sync health state. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_biometric_device_users` | Maps hardware biometric PINs/user IDs to HR employees. | `biometric_device_id` -> `hr_biometric_devices.id`; `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware |
| `hr_biometric_raw_punches` | Stores deduplicated raw biometric punch payloads staged for attendance processing. | `biometric_device_id` -> `hr_biometric_devices.id` | tenant scoped; organization-unit aware |
| `hr_attendance_geofences` | Stores GPS geofence coordinates, radiuses, and office IP CIDR whitelists. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_attendance_kiosks` | Stores dynamic TOTP QR kiosk terminals and HMAC rotation settings. | `geofence_id` -> `hr_attendance_geofences.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_employee_attendance_credentials` | Stores employee RFID cards, NFC tags, barcodes, and mobile device UUID bindings. | `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `hr_attendance_batch_imports` | Tracks two-phase CSV/Excel attendance import batches and validation summaries. | No declared foreign keys. | tenant scoped; organization-unit aware |

## Vehicle

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `vehicle_makes` | Stores vehicle make records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_types` | Stores reusable type definitions for vehicle type. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_categories` | Stores reusable classifications for vehicle category. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_models` | Stores vehicle model records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicles` | Stores vehicle master, identity numbers, and classifications. Ownership is stored in `vehicle_ownerships`. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `vehicle_documents` | Stores document metadata associated with vehicle document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_ownerships` | Stores vehicle ownership records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_attributes` | Stores vehicle attribute records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_status_histories` | Records status transitions and audit context for vehicle status history. | No declared foreign keys. | tenant scoped; organization-unit aware |

## VehicleRental

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `vehicle_rental_customer_agreements` | Governs business data, relationships, and lifecycle records for Vehicle Rental Customer Agreements within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_customer_agreements_history` | Stores immutable lifecycle and state transition history for Vehicle Rental Customer Agreements. | `tenant_id` -> `tenants` | tenant scoped; optimistic concurrency |
| `vehicle_rental_owner_agreements` | Governs business data, relationships, and lifecycle records for Vehicle Rental Owner Agreements within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_owner_agreements_history` | Stores immutable lifecycle and state transition history for Vehicle Rental Owner Agreements. | `tenant_id` -> `tenants` | tenant scoped; optimistic concurrency |
| `vehicle_rental_uses` | Governs business data, relationships, and lifecycle records for Vehicle Rental Uses within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_use_history` | Stores immutable lifecycle and state transition history for Vehicle Rental Use. | `tenant_id` -> `tenants` | tenant scoped; optimistic concurrency |
| `vehicle_rental_running_charts` | Governs business data, relationships, and lifecycle records for Vehicle Rental Running Charts within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_running_chart_history` | Stores immutable lifecycle and state transition history for Vehicle Rental Running Chart. | `tenant_id` -> `tenants` | tenant scoped; optimistic concurrency |
| `vehicle_rental_customer_base_charges` | Governs business data, relationships, and lifecycle records for Vehicle Rental Customer Base Charges within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_owner_base_charges` | Governs business data, relationships, and lifecycle records for Vehicle Rental Owner Base Charges within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_customer_usage_charges` | Governs business data, relationships, and lifecycle records for Vehicle Rental Customer Usage Charges within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_owner_usage_charges` | Governs business data, relationships, and lifecycle records for Vehicle Rental Owner Usage Charges within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_incidents` | Governs business data, relationships, and lifecycle records for Vehicle Rental Incidents within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_incident_events` | Records domain lifecycle events and audit trail for Vehicle Rental Incident. | `tenant_id` -> `tenants` | tenant scoped |
| `vehicle_rental_incident_attachments` | Governs business data, relationships, and lifecycle records for Vehicle Rental Incident Attachments within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped |
| `vehicle_rental_reservations` | Governs business data, relationships, and lifecycle records for Vehicle Rental Reservations within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_reservation_histories` | Stores immutable lifecycle and state transition history for Vehicle Rental Reservation. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `vehicle_rental_finance_agreements` | Governs business data, relationships, and lifecycle records for Vehicle Rental Finance Agreements within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_finance_installments` | Governs business data, relationships, and lifecycle records for Vehicle Rental Finance Installments within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_finance_status_histories` | Stores immutable lifecycle and state transition history for Vehicle Rental Finance Status. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `vehicle_rental_expenses` | Governs business data, relationships, and lifecycle records for Vehicle Rental Expenses within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_expense_allocations` | Maintains settlement and payment/inventory allocation records for Vehicle Rental Expense. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_agreement_adjustments` | Governs business data, relationships, and lifecycle records for Vehicle Rental Agreement Adjustments within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_agreement_adjustments_history` | Stores immutable lifecycle and state transition history for Vehicle Rental Agreement Adjustments. | `tenant_id` -> `tenants` | tenant scoped |
| `vehicle_rental_billing_cycles` | Governs business data, relationships, and lifecycle records for Vehicle Rental Billing Cycles within the VehicleRental module. | `tenant_id` -> `tenants`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_rental_billing_cycle_charts` | Governs business data, relationships, and lifecycle records for Vehicle Rental Billing Cycle Charts within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped |
| `vehicle_rental_billing_cycle_items` | Stores individual itemized line entries for Vehicle Rental Billing Cycle. | `tenant_id` -> `tenants` | tenant scoped |
| `vehicle_rental_reassignments` | Governs business data, relationships, and lifecycle records for Vehicle Rental Reassignments within the VehicleRental module. | `tenant_id` -> `tenants` | tenant scoped |

## VehicleService

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `vehicle_service_job_types` | Governs business data, relationships, and lifecycle records for Vehicle Service Job Types within the VehicleService module. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `vehicle_service_jobs` | Stores vehicle service job headers, customer/vehicle context, totals, and status. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |
| `vehicle_service_inspections` | Stores vehicle service inspection records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_job_lines` | Stores line-level detail and traceability for vehicle service job line. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_line_employees` | Stores vehicle service line employee records used by the owning module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_documents` | Stores document metadata associated with vehicle service document. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_service_invoice_links` | Stores traceable links between vehicle service invoice link and downstream documents. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_payment_links` | Stores traceable links between vehicle service payment link and downstream documents. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_status_histories` | Records status transitions and audit context for vehicle service status history. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_supervisor_commission_policies` | Governs business data, relationships, and lifecycle records for Vehicle Service Supervisor Commission Policies within the VehicleService module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_service_labor_item_commission_rules` | Governs business data, relationships, and lifecycle records for Vehicle Service Labor Item Commission Rules within the VehicleService module. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `vehicle_service_job_discounts` | Governs business data, relationships, and lifecycle records for Vehicle Service Job Discounts within the VehicleService module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_legacy_import_batches` | Governs business data, relationships, and lifecycle records for Vehicle Service Legacy Import Batches within the VehicleService module. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_legacy_histories` | Stores immutable lifecycle and state transition history for Vehicle Service Legacy. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_legacy_history_items` | Stores individual itemized line entries for Vehicle Service Legacy History. | No declared foreign keys. | tenant scoped; organization-unit aware |
| `vehicle_service_job_feedbacks` | Stores customer reviews, star ratings, and internal workshop notes for vehicle service jobs. | No declared foreign keys. | tenant scoped; organization-unit aware; soft deletes; optimistic concurrency |

## Reporting

No database tables. Reporting coordinates printable views, cash reconciliation analysis, and multi-format document streaming from source modules.

## Voucher

No independent database tables. Voucher provides unified debit/credit financial instrument printing over Payment and General Ledger records.

## Audit

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `audit_logs` | Immutable, append-only audit events with actor/scope snapshots and bounded sanitized payloads. | No declared foreign keys. | unique `audit_logs_event_uuid_uk`; unique `audit_logs_producer_fingerprint_uk`; tenant scoped; organization-unit aware |

## Payroll

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `payroll_salary_components` | Stores earning, deduction, and employer-contribution component definitions. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_salary_structures` | Stores reusable salary structure templates per pay cycle. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_salary_structure_items` | Stores component calculation rules within a salary structure. | `salary_structure_id` -> `payroll_salary_structures.id`; `salary_component_id` -> `payroll_salary_components.id` | tenant scoped; organization-unit aware |
| `payroll_employee_salary_assignments` | Stores employee base salary, hourly rate, structure assignment, and bank details. | `employee_id` -> `hr_employees.id`; `salary_structure_id` -> `payroll_salary_structures.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_tax_tables` | Stores progressive income tax table headers by effective year. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_tax_brackets` | Stores ordered progressive tax brackets and rates for a tax table. | `tax_table_id` -> `payroll_tax_tables.id` | tenant scoped; organization-unit aware |
| `payroll_statutory_rules` | Stores statutory contribution rules (EPF/ETF/social security) and rates. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_pay_periods` | Stores payroll calendar pay periods and open/closed lifecycle state. | No declared foreign keys. | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_pay_runs` | Stores batch pay run headers, totals, approval state, and GL posting references. | `pay_period_id` -> `payroll_pay_periods.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_payslips` | Stores individual employee payslip headers, worked/leave metrics, and net pay. | `pay_run_id` -> `payroll_pay_runs.id`; `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_payslip_items` | Stores itemized earnings, deductions, taxes, and statutory lines per payslip. | `payslip_id` -> `payroll_payslips.id`; `salary_component_id` -> `payroll_salary_components.id` | tenant scoped; organization-unit aware |
| `payroll_adjustments` | Stores ad-hoc, bonus, overtime, and retroactive arrears adjustments. | `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_employee_loans` | Stores employee loan and salary advance headers, principal, and balance. | `employee_id` -> `hr_employees.id` | tenant scoped; organization-unit aware; optimistic concurrency |
| `payroll_loan_repayments` | Stores scheduled and payslip-deducted loan repayment installments. | `employee_loan_id` -> `payroll_employee_loans.id` | tenant scoped; organization-unit aware |

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
