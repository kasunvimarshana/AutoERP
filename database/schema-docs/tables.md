# AutoERP Table Catalog

This catalog documents the schema produced by the module migrations. The enclosing section is the owning module for every listed table. Laravel infrastructure tables are included for completeness. The ownership catalogue was aligned with the canonical create migrations by static verification on 2026-06-28. Runtime database verification remains a release gate.

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
| `failed_jobs` | Stores failed job records used by the owning module. | No declared foreign keys. | unique `uuid` |
| `job_batches` | Stores job batch records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `jobs` | Stores job records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |
| `migrations` | Stores migration records used by the owning module. | No declared foreign keys. | Primary key and domain query indexes. |

## Core

No business tables. Core contains technical primitives, concurrency abstractions, exact decimal/time support, and domain-safe exceptions only.

## Idempotency

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `idempotency_records` | Owns replay-safe command/request result records for cross-module idempotent operations. | tenant and optional organization-unit scope; opaque source identity | unique idempotency identity per governed scope; explicit lifecycle and expiry |

## PrivateObject

No database tables. PrivateObject owns the private-storage capability and canonical object-key operations. Feature modules own their document metadata and lifecycle records.

## Tenant

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `tenant_documents` | Stores private tenant-owned file metadata and checksums. | `tenant_id` -> `tenants` | unique `tenant_id,name`; unique `storage_disk,storage_path`; tenant scoped; immutable storage ownership |
| `tenant_domains` | Stores tenant hostnames and DNS ownership-verification state. | `tenant_id` -> `tenants` | unique `domain`; one primary marker per tenant; verified active primary required for activation |
| `tenant_plans` | Stores SaaS subscription-plan definitions. | `currency_id` -> `currencies` | unique `slug`; status lifecycle; optimistic concurrency |
| `tenants` | Stores tenant identity, authoritative lifecycle, plan assignment, and base-accounting-currency invariant. | `tenant_plan_id` -> `tenant_plans`; `base_currency_id` -> `currencies` | unique `code`; unique `slug`; unique `uuid`; status is the lifecycle source of truth; optimistic concurrency |

## OrganizationUnit

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `organization_unit_documents` | Stores document metadata associated with organization unit document. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,organization_unit_id,name`; tenant scoped; organization-unit aware |
| `organization_unit_types` | Stores reusable type definitions for organization unit type. | `tenant_id` -> `tenants` | unique `tenant_id,name`; tenant scoped; soft deletes |
| `organization_units` | Stores the tenant organization hierarchy used for operational scoping. | `parent_id` -> `organization_units`; `type_id` -> `organization_unit_types`; `tenant_id` -> `tenants` | unique `tenant_id,name`; tenant scoped; soft deletes |

## User

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `permissions` | Stores permission records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,name,guard_name`; tenant scoped; organization-unit aware; soft deletes |
| `role_permissions` | Stores role permission records used by the owning module. | `permission_id` -> `permissions`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `role_id` -> `roles` | unique `tenant_id,role_id,permission_id`; tenant scoped; organization-unit aware |
| `roles` | Stores role records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,name,guard_name`; tenant scoped; organization-unit aware; soft deletes |
| `user_devices` | Stores user device records used by the owning module. | `organization_unit_id` -> `organization_units`; `user_id` -> `users`; `tenant_id` -> `tenants` | unique `tenant_id,user_id,device_token`; tenant scoped; organization-unit aware |
| `user_documents` | Stores document metadata associated with user document. | `organization_unit_id` -> `organization_units`; `user_id` -> `users`; `tenant_id` -> `tenants` | unique `tenant_id,user_id,name`; tenant scoped; organization-unit aware |
| `user_permissions` | Stores user permission records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `permission_id` -> `permissions`; `user_id` -> `users` | unique `tenant_id,user_id,permission_id`; tenant scoped; organization-unit aware |
| `user_roles` | Stores user role records used by the owning module. | `role_id` -> `roles`; `user_id` -> `users`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,user_id,role_id`; tenant scoped; organization-unit aware |
| `user_organization_units` | Stores explicit organization-unit access assignments for tenant-owned users. | composite (`user_id`,`tenant_id`) -> `users`; composite (`organization_unit_id`,`tenant_id`) -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,user_id,organization_unit_id`; at most one active default assignment per tenant user |
| `users` | Stores tenant-owned user identities and separate tenant-less SaaS platform-operator identities. | nullable `tenant_id` -> `tenants` | tenant users require `tenant_id`; platform operators require a globally unique platform login and no tenant ownership; organization access is owned by `user_organization_units`; soft deletes |

## Auth

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `auth_access_tokens` | Stores revocable authentication tokens for auth access token. | `identity_id` -> `auth_identities`; `provider_id` -> `auth_providers`; `tenant_id` -> `tenants`; `client_id` -> `auth_clients`; `organization_unit_id` -> `organization_units`; `session_id` -> `auth_sessions`; `user_id` -> `users` | globally unique `token_key`; explicit `tenant` or `platform` token scope; tenant scoped; organization-unit aware; soft deletes |
| `auth_authorization_codes` | Stores auth authorization code records used by the owning module. | `client_id` -> `auth_clients`; `organization_unit_id` -> `organization_units`; `session_id` -> `auth_sessions`; `user_id` -> `users`; `identity_id` -> `auth_identities`; `provider_id` -> `auth_providers`; `tenant_id` -> `tenants` | unique `tenant_id,code_key`; tenant scoped; organization-unit aware; soft deletes |
| `auth_clients` | Stores auth client records used by the owning module. | `provider_id` -> `auth_providers`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,client_key`; tenant scoped; organization-unit aware; soft deletes |
| `auth_identities` | Stores auth identity records used by the owning module. | `provider_id` -> `auth_providers`; `user_id` -> `users`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,provider_id,provider_user_key`; tenant scoped; organization-unit aware; soft deletes |
| `auth_login_attempts` | Records security attempts for monitoring and throttling. | `identity_id` -> `auth_identities`; `provider_id` -> `auth_providers`; `user_id` -> `users`; `client_id` -> `auth_clients`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `auth_providers` | Stores auth provider records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,provider_key`; tenant scoped; organization-unit aware; soft deletes |
| `auth_refresh_tokens` | Stores revocable authentication tokens for auth refresh token. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `access_token_id` -> `auth_access_tokens`; `identity_id` -> `auth_identities`; `session_id` -> `auth_sessions`; `user_id` -> `users`; `client_id` -> `auth_clients` | globally unique `refresh_key`; explicit `tenant` or `platform` token scope; atomic rotation; tenant scoped; organization-unit aware; soft deletes |
| `auth_sessions` | Stores authenticated session state for auth session. | `identity_id` -> `auth_identities`; `provider_id` -> `auth_providers`; `user_id` -> `users`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,session_key`; tenant scoped; organization-unit aware; soft deletes |
| `auth_verification_challenges` | Stores auth verification challenge records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `identity_id` -> `auth_identities`; `provider_id` -> `auth_providers`; `user_id` -> `users` | unique `tenant_id,challenge_key`; tenant scoped; organization-unit aware; soft deletes |

## Configuration

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `countries` | Provides reference country codes and names used by addresses and localization. | No declared foreign keys. | unique `code`; soft deletes |
| `currencies` | Provides currency definitions and precision used by financial documents. | No declared foreign keys. | unique `code`; soft deletes |
| `languages` | Stores language records used by the owning module. | No declared foreign keys. | unique `code`; soft deletes |
| `timezones` | Stores timezone records used by the owning module. | No declared foreign keys. | unique `name`; soft deletes |

## Sequence

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `sequences` | Generates tenant- and scope-specific business document numbers without hardcoded IDs. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,document_type,scope_key`; tenant scoped; organization-unit aware; soft deletes |

## UOM

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `unit_of_measures` | Stores generic tenant UOM definitions without module-specific usage flags. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; unique `tenant_id,name`; tenant scoped; organization-unit aware; soft deletes |
| `uom_conversions` | Stores uom conversion records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `to_uom_id` -> `unit_of_measures`; `from_uom_id` -> `unit_of_measures` | unique `tenant_id,from_uom_id,to_uom_id`; tenant scoped; organization-unit aware; soft deletes |

## Warehouse

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `warehouse_locations` | Stores warehouse location records used by the owning module. | `parent_id` -> `warehouse_locations`; `warehouse_id` -> `warehouses`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,warehouse_id,name`; tenant scoped; organization-unit aware; soft deletes |
| `warehouses` | Stores tenant warehouses and their organization-unit ownership. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,name`; tenant scoped; organization-unit aware; soft deletes |

## Finance

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `finance_account_balances` | Stores current or period balances for finance account balance. | `tenant_id` -> `tenants`; `fiscal_period_id` -> `finance_fiscal_periods`; `organization_unit_id` -> `organization_units`; `account_id` -> `finance_accounts`; `fiscal_year_id` -> `finance_fiscal_years` | unique `tenant_id,organization_unit_id,account_id,fiscal_period_id`; tenant scoped; organization-unit aware |
| `finance_account_categories` | Stores reusable classifications for finance account category. | `account_type_id` -> `finance_account_types`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped |
| `finance_account_types` | Stores reusable type definitions for finance account type. | `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped |
| `finance_accounts` | Stores the chart of accounts used for posting, balances, budgets, and bank accounts. | `account_category_id` -> `finance_account_categories`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `account_type_id` -> `finance_account_types`; `parent_id` -> `finance_accounts` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `finance_bank_reconciliations` | Stores finance bank reconciliation records used by the owning module. | `organization_unit_id` -> `organization_units`; `bank_account_id` -> `finance_accounts`; `tenant_id` -> `tenants` | unique `tenant_id,organization_unit_id,bank_account_id,statement_reference`; tenant scoped; organization-unit aware |
| `finance_bank_statement_lines` | Stores line-level detail and traceability for finance bank statement line. | `matched_ledger_entry_id` -> `finance_ledger_entries`; `reconciliation_id` -> `finance_bank_reconciliations`; `bank_account_id` -> `finance_accounts`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `finance_budget_lines` | Stores line-level detail and traceability for finance budget line. | `budget_id` -> `finance_budgets`; `fiscal_period_id` -> `finance_fiscal_periods`; `tenant_id` -> `tenants`; `account_id` -> `finance_accounts`; `dimension_id` -> `finance_dimensions`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `finance_budgets` | Stores finance budget records used by the owning module. | `fiscal_year_id` -> `finance_fiscal_years`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,organization_unit_id,budget_year,name`; tenant scoped; organization-unit aware |
| `finance_dimensions` | Stores finance dimension records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware |
| `finance_fiscal_periods` | Stores finance fiscal period records used by the owning module. | `organization_unit_id` -> `organization_units`; `fiscal_year_id` -> `finance_fiscal_years`; `tenant_id` -> `tenants` | unique `fiscal_year_id,period_number`; tenant scoped; organization-unit aware |
| `finance_fiscal_years` | Stores finance fiscal year records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,organization_unit_id,start_date,end_date`; tenant scoped; organization-unit aware |
| `finance_journal_entries` | Stores balanced accounting journal headers and posting traceability. | `posting_profile_id` -> `finance_posting_profiles`; `tenant_id` -> `tenants`; `fiscal_period_id` -> `finance_fiscal_periods`; `organization_unit_id` -> `organization_units`; `reversal_of_id` -> `finance_journal_entries`; `currency_id` -> `currencies`; `fiscal_year_id` -> `finance_fiscal_years`; polymorphic `source_type`/`source_id` source | unique `tenant_id,journal_number`; tenant scoped; organization-unit aware; soft deletes |
| `finance_journal_lines` | Stores line-level detail and traceability for finance journal line. | `organization_unit_id` -> `organization_units`; `account_id` -> `finance_accounts`; `journal_entry_id` -> `finance_journal_entries`; `tenant_id` -> `tenants`; `dimension_id` -> `finance_dimensions` | tenant scoped; organization-unit aware |
| `finance_ledger_entries` | Stores immutable posted debit and credit entries by account and dimension. | `organization_unit_id` -> `organization_units`; `dimension_id` -> `finance_dimensions`; `fiscal_year_id` -> `finance_fiscal_years`; `journal_line_id` -> `finance_journal_lines`; `tenant_id` -> `tenants`; `account_id` -> `finance_accounts`; `fiscal_period_id` -> `finance_fiscal_periods`; `journal_entry_id` -> `finance_journal_entries`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `finance_posting_profile_rules` | Stores finance posting profile rule records used by the owning module. | `account_id` -> `finance_accounts`; `posting_profile_id` -> `finance_posting_profiles` | unique `posting_profile_id,line_key` |
| `finance_posting_profiles` | Stores finance posting profile records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,organization_unit_id,code`; tenant scoped; organization-unit aware |

## Tax

`customer_tax_profiles` and `supplier_tax_profiles` are intentionally Tax-owned extension tables. Their migrations run after Customer and Supplier because they enforce FKs to those module-owned tables.

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `customer_tax_profiles` | Stores tax registration and default tax behavior for customer tax profile. | `customer_id` -> `customers`; `tax_group_id` -> `tax_groups`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `customer_id`; tenant scoped; organization-unit aware |
| `supplier_tax_profiles` | Stores tax registration and default tax behavior for supplier tax profile. | `supplier_id` -> `suppliers`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `tax_group_id` -> `tax_groups` | unique `supplier_id`; tenant scoped; organization-unit aware |
| `tax_document_snapshots` | Stores immutable calculation snapshots for tax document snapshot. | `tax_id` -> `taxes`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `tax_group_lines` | Stores line-level detail and traceability for tax group line. | `tax_group_id` -> `tax_groups`; `tax_id` -> `taxes` | unique `tax_group_id,sequence`; unique `tax_group_id,tax_id` |
| `tax_groups` | Stores tax group records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,organization_unit_id,code`; tenant scoped; organization-unit aware |
| `tax_posting_profiles` | Stores tax posting profile records used by the owning module. | `account_id` -> `finance_accounts`; `tax_id` -> `taxes`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,organization_unit_id,tax_id,direction`; tenant scoped; organization-unit aware |
| `tax_rates` | Stores effective rates used by tax rate. | `tax_id` -> `taxes` | Primary key and domain query indexes. |
| `tax_transactions` | Stores tax transaction records used by the owning module. | `account_id` -> `finance_accounts`; `tax_document_snapshot_id` -> `tax_document_snapshots`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `tax_id` -> `taxes`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `taxes` | Stores tenant tax definitions and calculation behavior. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware |

## Item

Tax override columns remain on the Item-owned `items` table because Item services, requests, resources, and frontend payloads directly own those fields. The Item create migration therefore runs after the Tax master tables.

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `item_base_uom_revisions` | Records controlled revisions and change context for item base uom revision. | `created_by` -> `users`; `new_base_uom_id` -> `unit_of_measures`; `organization_unit_id` -> `organization_units`; `applied_by` -> `users`; `item_id` -> `items`; `old_base_uom_id` -> `unit_of_measures`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `item_brands` | Stores item brand records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `item_bundles` | Stores item bundle records used by the owning module. | `tenant_id` -> `tenants`; `child_variant_id` -> `item_variants`; `parent_item_id` -> `items`; `uom_id` -> `unit_of_measures`; `child_item_id` -> `items`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `item_categories` | Stores reusable classifications for item category. | `parent_id` -> `item_categories`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `item_codes` | Stores item code records used by the owning module. | `item_variant_id` -> `item_variants`; `tenant_id` -> `tenants`; `item_id` -> `items`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code_type,code`; tenant scoped; organization-unit aware |
| `item_prices` | Stores item price records used by the owning module. | `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `uom_id` -> `unit_of_measures`; `currency_id` -> `currencies`; `item_variant_id` -> `item_variants`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `item_units` | Stores item unit records used by the owning module. | `item_id` -> `items`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `uom_id` -> `unit_of_measures` | unique `item_id,uom_id,unit_role`; tenant scoped; organization-unit aware |
| `item_usage_rules` | Stores item usage rule records used by the owning module. | `organization_unit_id` -> `organization_units`; `item_id` -> `items`; `tenant_id` -> `tenants` | unique `tenant_id,item_id,module_code`; tenant scoped; organization-unit aware |
| `item_variants` | Stores item variant records used by the owning module. | `item_id` -> `items`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `items` | Stores the tenant item master, base UOM, identifiers, and operational settings. | `item_category_id` -> `item_categories`; `purchase_tax_group_id` -> `tax_groups`; `tenant_id` -> `tenants`; `base_uom_id` -> `unit_of_measures`; `item_brand_id` -> `item_brands`; `organization_unit_id` -> `organization_units`; `sales_tax_group_id` -> `tax_groups`; `default_tax_group_id` -> `tax_groups` | unique `tenant_id,barcode`; unique `tenant_id,code`; unique `tenant_id,sku`; tenant scoped; organization-unit aware; soft deletes |

## Supplier

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `supplier_addresses` | Stores address records and address roles for supplier address. | `supplier_id` -> `suppliers`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; soft deletes |
| `supplier_bank_accounts` | Stores bank account details for supplier bank account. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `currency_id` -> `currencies`; `supplier_id` -> `suppliers` | unique `supplier_id,account_number`; tenant scoped; organization-unit aware; soft deletes |
| `supplier_categories` | Stores reusable classifications for supplier category. | `parent_id` -> `supplier_categories`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `supplier_category_assignments` | Maps supplier category assignment records while preventing duplicate assignments. | `organization_unit_id` -> `organization_units`; `supplier_id` -> `suppliers`; `supplier_category_id` -> `supplier_categories`; `tenant_id` -> `tenants` | unique `supplier_id,supplier_category_id`; tenant scoped; organization-unit aware |
| `supplier_contacts` | Stores contact persons and communication details for supplier contact. | `tenant_id` -> `tenants`; `supplier_id` -> `suppliers`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware; soft deletes |
| `supplier_credit_profiles` | Stores credit terms, limits, and controls for supplier credit profile. | `supplier_id` -> `suppliers`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `supplier_id`; tenant scoped; organization-unit aware |
| `supplier_documents` | Stores document metadata associated with supplier document. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `supplier_id` -> `suppliers` | tenant scoped; organization-unit aware; soft deletes |
| `supplier_item_mappings` | Stores supplier item mapping records used by the owning module. | `item_variant_id` -> `item_variants`; `supplier_id` -> `suppliers`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `default_purchase_uom_id` -> `unit_of_measures` | unique `supplier_id,item_id,item_variant_id`; tenant scoped; organization-unit aware; soft deletes |
| `supplier_status_histories` | Records status transitions and audit context for supplier status history. | `tenant_id` -> `tenants`; `supplier_id` -> `suppliers`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `suppliers` | Stores supplier master data and tenant-scoped supplier identifiers. | `default_currency_id` -> `currencies`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; unique `tenant_id,supplier_number`; tenant scoped; organization-unit aware; soft deletes |

## Customer

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `customer_addresses` | Stores address records and address roles for customer address. | `organization_unit_id` -> `organization_units`; `customer_id` -> `customers`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; soft deletes |
| `customer_bank_accounts` | Stores bank account details for customer bank account. | `organization_unit_id` -> `organization_units`; `customer_id` -> `customers`; `tenant_id` -> `tenants`; `currency_id` -> `currencies` | unique `customer_id,account_number`; tenant scoped; organization-unit aware; soft deletes |
| `customer_categories` | Stores reusable classifications for customer category. | `parent_id` -> `customer_categories`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `customer_category_assignments` | Maps customer category assignment records while preventing duplicate assignments. | `customer_category_id` -> `customer_categories`; `organization_unit_id` -> `organization_units`; `customer_id` -> `customers`; `tenant_id` -> `tenants` | unique `customer_id,customer_category_id`; tenant scoped; organization-unit aware |
| `customer_contacts` | Stores contact persons and communication details for customer contact. | `customer_id` -> `customers`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware; soft deletes |
| `customer_credit_profiles` | Stores credit terms, limits, and controls for customer credit profile. | `organization_unit_id` -> `organization_units`; `customer_id` -> `customers`; `tenant_id` -> `tenants` | unique `customer_id`; tenant scoped; organization-unit aware |
| `customer_documents` | Stores document metadata associated with customer document. | `customer_id` -> `customers`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware; soft deletes |
| `customer_status_histories` | Records status transitions and audit context for customer status history. | `customer_id` -> `customers`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `customers` | Stores customer master data and tenant-scoped customer identifiers. | `default_currency_id` -> `currencies`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; unique `tenant_id,customer_number`; tenant scoped; organization-unit aware; soft deletes |

## Vehicle

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `vehicle_attributes` | Stores vehicle attribute records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_id` -> `vehicles` | unique `vehicle_id,attribute_key`; tenant scoped; organization-unit aware |
| `vehicle_categories` | Stores reusable classifications for vehicle category. | `parent_id` -> `vehicle_categories`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `vehicle_documents` | Stores document metadata associated with vehicle document. | `organization_unit_id` -> `organization_units`; `vehicle_id` -> `vehicles`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_makes` | Stores vehicle make records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `vehicle_models` | Stores vehicle model records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_make_id` -> `vehicle_makes` | unique `tenant_id,vehicle_make_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `vehicle_ownerships` | Stores vehicle ownership records used by the owning module. | `owner_type` + `owner_id` identify the owning party; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_id` -> `vehicles` | tenant scoped; organization-unit aware |
| `vehicle_status_histories` | Records status transitions and audit context for vehicle status history. | `vehicle_id` -> `vehicles`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `vehicle_types` | Stores reusable type definitions for vehicle type. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `vehicles` | Stores vehicle master, identity numbers, and classifications. Ownership is stored in `vehicle_ownerships`. | `vehicle_make_id` -> `vehicle_makes`; `vehicle_type_id` -> `vehicle_types`; `organization_unit_id` -> `organization_units`; `vehicle_category_id` -> `vehicle_categories`; `vehicle_model_id` -> `vehicle_models`; `tenant_id` -> `tenants` | unique `tenant_id,chassis_number`; unique `tenant_id,code`; unique `tenant_id,engine_number`; unique `tenant_id,vehicle_number`; unique `tenant_id,registration_number`; unique `tenant_id,vin_number`; tenant scoped; organization-unit aware; soft deletes |

## Hr

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `hr_certifications` | Stores hr certification records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `hr_departments` | Stores hr department records used by the owning module. | `parent_id` -> `hr_departments`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `hr_designations` | Stores hr designation records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_addresses` | Stores address records and address roles for hr employee address. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees` | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_availabilities` | Stores hr employee availability records used by the owning module. | `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `hr_employee_certification_assignments` | Maps hr employee certification assignment records while preventing duplicate assignments. | `tenant_id` -> `tenants`; `employee_id` -> `hr_employees`; `organization_unit_id` -> `organization_units`; `certification_id` -> `hr_certifications` | unique `employee_id,certification_id`; tenant scoped; organization-unit aware |
| `hr_employee_contacts` | Stores contact persons and communication details for hr employee contact. | `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_documents` | Stores document metadata associated with hr employee document. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees` | tenant scoped; organization-unit aware; soft deletes |
| `hr_employee_license_assignments` | Maps hr employee license assignment records while preventing duplicate assignments. | `employee_id` -> `hr_employees`; `tenant_id` -> `tenants`; `license_id` -> `hr_licenses`; `organization_unit_id` -> `organization_units` | unique `employee_id,license_id`; tenant scoped; organization-unit aware |
| `hr_employee_rates` | Stores effective rates used by hr employee rate. | `tenant_id` -> `tenants`; `employee_id` -> `hr_employees`; `organization_unit_id` -> `organization_units`; `currency_id` -> `currencies` | tenant scoped; organization-unit aware |
| `hr_employee_skill_assignments` | Maps hr employee skill assignment records while preventing duplicate assignments. | `tenant_id` -> `tenants`; `skill_id` -> `hr_skills`; `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees` | unique `employee_id,skill_id`; tenant scoped; organization-unit aware |
| `hr_employee_status_histories` | Records status transitions and audit context for hr employee status history. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `employee_id` -> `hr_employees` | tenant scoped; organization-unit aware |
| `hr_employees` | Stores hr employee records used by the owning module. | `employment_type_id` -> `hr_employment_types`; `reporting_manager_id` -> `hr_employees`; `designation_id` -> `hr_designations`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `department_id` -> `hr_departments` | unique `tenant_id,code`; unique `tenant_id,employee_number`; tenant scoped; organization-unit aware; soft deletes |
| `hr_employment_types` | Stores reusable type definitions for hr employment type. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `hr_licenses` | Stores hr license records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `hr_skills` | Stores hr skill records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |

## Inventory

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `inventory_adjustment_lines` | Stores line-level detail and traceability for inventory adjustment line. | `batch_id` -> `inventory_batches`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `inventory_adjustment_id` -> `inventory_adjustments`; `item_variant_id` -> `item_variants`; `serial_number_id` -> `inventory_serial_numbers` | tenant scoped; organization-unit aware |
| `inventory_adjustments` | Stores inventory adjustment records used by the owning module. | `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations` | unique `tenant_id,adjustment_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_allocation_issues` | Stores inventory allocation issue records used by the owning module. | `allocation_line_id` -> `inventory_allocation_lines`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `allocation_id` -> `inventory_allocations`; `movement_id` -> `inventory_movements`; `reversal_movement_id` -> `inventory_movements` | unique `movement_id`; tenant scoped; organization-unit aware |
| `inventory_allocation_lines` | Stores line-level detail and traceability for inventory allocation line. | `serial_number_id` -> `inventory_serial_numbers`; `tenant_id` -> `tenants`; `allocation_id` -> `inventory_allocations`; `organization_unit_id` -> `organization_units`; `stock_balance_id` -> `inventory_stock_balances`; `batch_id` -> `inventory_batches` | tenant scoped; organization-unit aware |
| `inventory_allocations` | Stores controlled allocations between related inventory allocation records. | `serial_number_id` -> `inventory_serial_numbers`; `warehouse_id` -> `warehouses`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants`; `reservation_id` -> `inventory_reservations`; `tenant_id` -> `tenants`; `base_uom_id` -> `unit_of_measures`; `warehouse_location_id` -> `warehouse_locations`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | unique `tenant_id,allocation_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_batches` | Stores inventory batch records used by the owning module. | `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `item_variant_id` -> `item_variants`; `tenant_id` -> `tenants` | unique `tenant_id,item_id,batch_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_cost_adjustment_lines` | Stores line-level detail and traceability for inventory cost adjustment line. | `organization_unit_id` -> `organization_units`; `valuation_layer_id` -> `inventory_valuation_layers`; `inventory_cost_adjustment_id` -> `inventory_cost_adjustments`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `inventory_cost_adjustments` | Stores inventory cost adjustment records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,adjustment_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_movements` | Stores traceable stock state and location movements from business documents. | `organization_unit_id` -> `organization_units`; `batch_id` -> `inventory_batches`; `serial_number_id` -> `inventory_serial_numbers`; `warehouse_id` -> `warehouses`; `item_variant_id` -> `item_variants`; `base_uom_id` -> `unit_of_measures`; `reversal_of_id` -> `inventory_movements`; `item_id` -> `items`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; polymorphic `source_type`/`source_id` source | unique `tenant_id,movement_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_reservations` | Stores inventory reservation records used by the owning module. | `warehouse_location_id` -> `warehouse_locations`; `base_uom_id` -> `unit_of_measures`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | unique `tenant_id,reservation_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_serial_numbers` | Stores inventory serial number records used by the owning module. | `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `item_id` -> `items`; polymorphic `source_type`/`source_id` source | unique `tenant_id,serial_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_stock_balances` | Stores current quantity buckets by item, warehouse, location, batch, and scope. | `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants` | unique `tenant_id,organization_unit_id,item_id,item_variant_id,warehouse_id,warehouse_location_id,batch_id`; tenant scoped; organization-unit aware |
| `inventory_stock_count_lines` | Stores line-level detail and traceability for inventory stock count line. | `item_variant_id` -> `item_variants`; `serial_number_id` -> `inventory_serial_numbers`; `inventory_adjustment_line_id` -> `inventory_adjustment_lines`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `batch_id` -> `inventory_batches`; `inventory_stock_count_id` -> `inventory_stock_counts` | tenant scoped; organization-unit aware |
| `inventory_stock_counts` | Stores inventory stock count records used by the owning module. | `warehouse_id` -> `warehouses`; `inventory_adjustment_id` -> `inventory_adjustments`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `organization_unit_id` -> `organization_units` | unique `tenant_id,count_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_stock_state_changes` | Stores inventory stock state change records used by the owning module. | `serial_number_id` -> `inventory_serial_numbers`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `stock_balance_id` -> `inventory_stock_balances`; `warehouse_id` -> `warehouses`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `inventory_transfer_lines` | Stores line-level detail and traceability for inventory transfer line. | `tenant_id` -> `tenants`; `inbound_movement_id` -> `inventory_movements`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `serial_number_id` -> `inventory_serial_numbers`; `batch_id` -> `inventory_batches`; `inventory_transfer_id` -> `inventory_transfers`; `item_variant_id` -> `item_variants`; `outbound_movement_id` -> `inventory_movements` | tenant scoped; organization-unit aware |
| `inventory_transfers` | Stores inventory transfer records used by the owning module. | `from_warehouse_location_id` -> `warehouse_locations`; `tenant_id` -> `tenants`; `to_warehouse_location_id` -> `warehouse_locations`; `from_warehouse_id` -> `warehouses`; `organization_unit_id` -> `organization_units`; `to_warehouse_id` -> `warehouses` | unique `tenant_id,transfer_number`; tenant scoped; organization-unit aware; soft deletes |
| `inventory_valuation_consumptions` | Stores inventory valuation consumption records used by the owning module. | `issue_movement_id` -> `inventory_movements`; `reversed_by_movement_id` -> `inventory_movements`; `valuation_layer_id` -> `inventory_valuation_layers`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `issue_movement_id,valuation_layer_id`; tenant scoped; organization-unit aware |
| `inventory_valuation_layers` | Stores inventory cost layers used to value and consume stock. | `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `batch_id` -> `inventory_batches`; `item_variant_id` -> `item_variants`; `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; `base_uom_id` -> `unit_of_measures`; `item_id` -> `items`; `movement_id` -> `inventory_movements`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |

## Invoice

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `invoice_adjustment_allocations` | Stores controlled allocations between related invoice adjustment allocation records. | `invoice_id` -> `invoices`; `tenant_id` -> `tenants`; `invoice_adjustment_id` -> `invoice_adjustments`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | unique `invoice_id,source_adjustment_type,source_adjustment_id`; tenant scoped; organization-unit aware |
| `invoice_adjustments` | Stores invoice adjustment records used by the owning module. | `organization_unit_id` -> `organization_units`; `invoice_id` -> `invoices`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `invoice_balances` | Stores current or period balances for invoice balance. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `invoice_id` -> `invoices` | unique `invoice_id`; tenant scoped; organization-unit aware |
| `invoice_credit_allocations` | Stores controlled allocations between related invoice credit allocation records. | `invoice_id` -> `invoices`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `invoice_lines` | Stores line-level detail and traceability for invoice line. | `organization_unit_id` -> `organization_units`; `uom_id` -> `unit_of_measures`; `invoice_id` -> `invoices`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `invoice_source_lines` | Stores line-level detail and traceability for invoice source line. | `invoice_id` -> `invoices`; `organization_unit_id` -> `organization_units`; `invoice_line_id` -> `invoice_lines`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |
| `invoice_sources` | Stores invoice source records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `invoice_id` -> `invoices`; polymorphic `source_type`/`source_id` source | unique `invoice_id,source_type,source_id`; tenant scoped; organization-unit aware |
| `invoices` | Stores receivable and payable invoice headers independent of source documents. | `currency_id` -> `currencies`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,invoice_number`; tenant scoped; organization-unit aware; soft deletes |

## Payment

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `cheque_print_logs` | Records operational events for cheque print log. | `cheque_template_id` -> `cheque_templates`; `payment_id` -> `payments`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `cheque_templates` | Stores cheque template records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware; soft deletes |
| `payment_allocations` | Stores controlled allocations between related payment allocation records. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `invoice_id` -> `invoices`; `payment_id` -> `payments` | tenant scoped; organization-unit aware |
| `payment_lines` | Stores line-level detail and traceability for payment line. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `payment_method_id` -> `payment_methods`; `payment_id` -> `payments` | tenant scoped; organization-unit aware |
| `payment_methods` | Stores payment method records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,code`; tenant scoped; organization-unit aware; soft deletes |
| `payment_refunds` | Stores payment refund records used by the owning module. | `organization_unit_id` -> `organization_units`; `payment_method_id` -> `payment_methods`; `payment_id` -> `payments`; `tenant_id` -> `tenants` | unique `tenant_id,refund_number`; tenant scoped; organization-unit aware; soft deletes |
| `payment_reversals` | Stores payment reversal records used by the owning module. | `payment_id` -> `payments`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `payment_id`; unique `tenant_id,reversal_number`; tenant scoped; organization-unit aware |
| `payment_status_histories` | Records status transitions and audit context for payment status history. | `tenant_id` -> `tenants`; `payment_id` -> `payments`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware |
| `payment_unapplied_balances` | Stores current or period balances for payment unapplied balance. | `payment_id` -> `payments`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | unique `payment_id`; tenant scoped; organization-unit aware |
| `payments` | Stores inbound and outbound payment headers, source traceability, and cheque details. | `currency_id` -> `currencies`; `tenant_id` -> `tenants`; `bank_account_id` -> `finance_accounts`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | unique `tenant_id,payment_number`; tenant scoped; organization-unit aware; soft deletes |
| `global_configuration_values` | Registered global configuration overrides. | No foreign keys. | unique `key`; compare-and-swap `row_version` |
| `tenant_configuration_values` | Registered tenant configuration overrides. | `tenant_id` -> `tenants` | unique `tenant_id,key`; tenant scoped; compare-and-swap `row_version` |
| `organization_unit_configuration_values` | Registered organization-unit configuration overrides. | composite `organization_unit_id,tenant_id` -> `organization_units.id,tenant_id`; `tenant_id` -> `tenants` | unique scope + `key`; cross-tenant ownership prevented |

## Purchase

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `goods_receipt_note_lines` | Stores line-level detail and traceability for goods receipt note line. | `goods_receipt_note_id` -> `goods_receipt_notes`; `item_id` -> `items`; `ordered_uom_id` -> `unit_of_measures`; `purchase_order_line_id` -> `purchase_order_lines`; `base_uom_id` -> `unit_of_measures`; `uom_id` -> `unit_of_measures`; `inventory_movement_id` -> `inventory_movements`; `item_variant_id` -> `item_variants`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `goods_receipt_notes` | Stores goods receipt note records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `purchase_order_id` -> `purchase_orders`; `warehouse_id` -> `warehouses` | unique `tenant_id,grn_number`; tenant scoped; organization-unit aware; soft deletes |
| `purchase_debit_notes` | Stores purchase debit note records used by the owning module. | `purchase_return_id` -> `purchase_returns`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; polymorphic `source_type`/`source_id` source | unique `tenant_id,debit_note_number`; tenant scoped; organization-unit aware; soft deletes |
| `purchase_header_adjustments` | Stores purchase header adjustment records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware; soft deletes |
| `purchase_invoice_links` | Stores traceable links between purchase invoice link and downstream documents. | `invoice_id` -> `invoices`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | unique `invoice_id,source_type,source_id`; tenant scoped; organization-unit aware |
| `purchase_order_lines` | Stores line-level detail and traceability for purchase order line. | `purchase_order_id` -> `purchase_orders`; `uom_id` -> `unit_of_measures`; `base_uom_id` -> `unit_of_measures`; `item_variant_id` -> `item_variants`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `item_id` -> `items`; `ordered_uom_id` -> `unit_of_measures` | tenant scoped; organization-unit aware |
| `purchase_orders` | Stores supplier purchase order headers, totals, approvals, and lifecycle state. | `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses` | unique `tenant_id,purchase_order_number`; tenant scoped; organization-unit aware; soft deletes |
| `purchase_return_adjustment_allocations` | Stores controlled allocations between related purchase return adjustment allocation records. | `purchase_header_adjustment_id` -> `purchase_header_adjustments`; `purchase_return_id` -> `purchase_returns`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `purchase_return_lines` | Stores line-level detail and traceability for purchase return line. | `inventory_movement_id` -> `inventory_movements`; `item_variant_id` -> `item_variants`; `purchase_return_id` -> `purchase_returns`; `uom_id` -> `unit_of_measures`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `purchase_returns` | Stores purchase return records used by the owning module. | `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `organization_unit_id` -> `organization_units`; `warehouse_id` -> `warehouses`; polymorphic `source_type`/`source_id` source | unique `tenant_id,return_number`; tenant scoped; organization-unit aware; soft deletes |

## Sales

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `sales_credit_notes` | Stores sales credit note records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `customer_id` -> `customers`; `sales_return_id` -> `sales_returns` | unique `tenant_id,credit_note_number`; tenant scoped; organization-unit aware; soft deletes |
| `sales_deliveries` | Stores sales delivery records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `customer_id` -> `customers`; `sales_order_id` -> `sales_orders`; `warehouse_id` -> `warehouses` | unique `tenant_id,delivery_number`; tenant scoped; organization-unit aware; soft deletes |
| `sales_delivery_lines` | Stores line-level detail and traceability for sales delivery line. | `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `sales_order_line_id` -> `sales_order_lines`; `uom_id` -> `unit_of_measures`; `inventory_movement_id` -> `inventory_movements`; `item_variant_id` -> `item_variants`; `sales_delivery_id` -> `sales_deliveries`; `tenant_id` -> `tenants` | tenant scoped; organization-unit aware |
| `sales_header_adjustments` | Stores sales header adjustment records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware; soft deletes |
| `sales_invoice_links` | Stores traceable links between sales invoice link and downstream documents. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `invoice_id` -> `invoices`; polymorphic `source_type`/`source_id` source | unique `invoice_id,source_type,source_id`; tenant scoped; organization-unit aware |
| `sales_order_lines` | Stores line-level detail and traceability for sales order line. | `item_variant_id` -> `item_variants`; `organization_unit_id` -> `organization_units`; `sales_order_id` -> `sales_orders`; `base_uom_id` -> `unit_of_measures`; `item_id` -> `items`; `ordered_uom_id` -> `unit_of_measures`; `quotation_line_id` -> `sales_quotation_lines`; `tenant_id` -> `tenants`; `inventory_allocation_id` -> `inventory_allocations` | unique `sales_order_id,line_number`; tenant scoped; organization-unit aware |
| `sales_orders` | Stores customer sales order headers, totals, approvals, and lifecycle state. | `warehouse_location_id` -> `warehouse_locations`; `customer_id` -> `customers`; `quotation_id` -> `sales_quotations`; `warehouse_id` -> `warehouses`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,sales_order_number`; tenant scoped; organization-unit aware; soft deletes |
| `sales_quotation_lines` | Stores line-level detail and traceability for sales quotation line. | `item_variant_id` -> `item_variants`; `sales_quotation_id` -> `sales_quotations`; `uom_id` -> `unit_of_measures`; `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `sales_quotation_id,line_number`; tenant scoped; organization-unit aware |
| `sales_quotations` | Stores sales quotation records used by the owning module. | `customer_id` -> `customers`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | unique `tenant_id,quotation_number`; tenant scoped; organization-unit aware; soft deletes |
| `sales_return_adjustment_allocations` | Stores controlled allocations between related sales return adjustment allocation records. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `sales_header_adjustment_id` -> `sales_header_adjustments`; `sales_return_id` -> `sales_returns` | unique `sales_return_id,sales_header_adjustment_id`; tenant scoped; organization-unit aware |
| `sales_return_lines` | Stores line-level detail and traceability for sales return line. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `inventory_movement_id` -> `inventory_movements`; `item_variant_id` -> `item_variants`; `sales_return_id` -> `sales_returns`; `uom_id` -> `unit_of_measures`; `item_id` -> `items` | tenant scoped; organization-unit aware |
| `sales_returns` | Stores sales return records used by the owning module. | `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `warehouse_location_id` -> `warehouse_locations`; `customer_id` -> `customers`; `replacement_sales_order_id` -> `sales_orders`; `warehouse_id` -> `warehouses` | unique `tenant_id,return_number`; tenant scoped; organization-unit aware; soft deletes |
| `sales_status_histories` | Records status transitions and audit context for sales status history. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; polymorphic `source_type`/`source_id` source | tenant scoped; organization-unit aware |

## VehicleService

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `vehicle_service_documents` | Stores document metadata associated with vehicle service document. | `vehicle_service_job_id` -> `vehicle_service_jobs`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units` | tenant scoped; organization-unit aware; soft deletes |
| `vehicle_service_inspections` | Stores vehicle service inspection records used by the owning module. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_service_job_id` -> `vehicle_service_jobs` | unique `vehicle_service_job_id`; tenant scoped; organization-unit aware |
| `vehicle_service_invoice_links` | Stores traceable links between vehicle service invoice link and downstream documents. | `invoice_id` -> `invoices`; `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_service_job_id` -> `vehicle_service_jobs` | unique `vehicle_service_job_id,invoice_id`; tenant scoped; organization-unit aware |
| `vehicle_service_job_lines` | Stores line-level detail and traceability for vehicle service job line. | `item_id` -> `items`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants`; `vehicle_service_job_id` -> `vehicle_service_jobs`; `inventory_movement_id` -> `inventory_movements`; `item_variant_id` -> `item_variants`; `parent_line_id` -> `vehicle_service_job_lines`; `uom_id` -> `unit_of_measures` | unique `vehicle_service_job_id,line_number`; tenant scoped; organization-unit aware |
| `vehicle_service_jobs` | Stores vehicle service job headers, customer/vehicle context, totals, and status. | `customer_id` -> `customers`; `supervisor_employee_id` -> `hr_employees`; `vehicle_id` -> `vehicles`; `organization_unit_id` -> `organization_units`; `tenant_id` -> `tenants` | unique `tenant_id,job_number`; tenant scoped; organization-unit aware; soft deletes |
| `vehicle_service_line_employees` | Stores vehicle service line employee records used by the owning module. | `employee_id` -> `hr_employees`; `organization_unit_id` -> `organization_units`; `vehicle_service_job_id` -> `vehicle_service_jobs`; `vehicle_service_job_line_id` -> `vehicle_service_job_lines`; `tenant_id` -> `tenants` | unique `vehicle_service_job_line_id,employee_id,role_type`; tenant scoped; organization-unit aware |
| `vehicle_service_payment_links` | Stores traceable links between vehicle service payment link and downstream documents. | `tenant_id` -> `tenants`; `invoice_id` -> `invoices`; `payment_id` -> `payments`; `vehicle_service_job_id` -> `vehicle_service_jobs`; `organization_unit_id` -> `organization_units` | unique `vehicle_service_job_id,payment_id,invoice_id`; tenant scoped; organization-unit aware |
| `vehicle_service_status_histories` | Records status transitions and audit context for vehicle service status history. | `tenant_id` -> `tenants`; `organization_unit_id` -> `organization_units`; `vehicle_service_job_id` -> `vehicle_service_jobs` | tenant scoped; organization-unit aware |

## Reporting

No persisted tables. Reporting reads domain-owned data and does not duplicate business records.

## Audit

| Table | Business purpose | Key relationships | Important constraints |
| --- | --- | --- | --- |
| `audit_logs` | Immutable, append-only audit events with actor/scope snapshots and bounded sanitized payloads. | canonical subject/source references; no lifecycle foreign keys | tenant and organization scoped; permission-controlled reads; no updates or deletes |
