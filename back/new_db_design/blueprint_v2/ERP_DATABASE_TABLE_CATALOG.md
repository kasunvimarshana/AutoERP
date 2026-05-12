# ERP Database Table Catalog

This catalog describes the new canonical schema table by table.

## 1. Platform And Tenancy

### tenants
Purpose: top-level tenant record.
Key columns:
- id
- tenant_code
- name
- legal_name
- base_currency_id
- timezone
- locale
- status_code
- activated_at
- suspended_at
- created_at
- updated_at
Notes:
- Permanent table
- No soft delete in production unless business requires tenant recovery workflow

### tenant_domains
Purpose: map domains and subdomains to tenants.
Key columns:
- id
- tenant_id
- domain
- is_primary
- is_verified
- verified_at
Notes:
- Unique domain globally

### tenant_settings
Purpose: low-frequency tenant configuration store.
Key columns:
- id
- tenant_id
- setting_group
- setting_key
- setting_value_json
Notes:
- Unique on tenant_id + setting_group + setting_key

### id_sequences
Purpose: document and movement numbering by tenant and scope.
Key columns:
- id
- tenant_id
- sequence_key
- scope_key
- prefix
- last_number
- padding_length
Notes:
- Replaces scattered next_sequence fields across modules

## 2. Identity And Access

### users
Purpose: application users.
Key columns:
- id
- tenant_id
- party_id
- email
- password_hash
- locale
- timezone
- status_code
- last_login_at
Notes:
- party_id links user to person or organization party record when needed

### roles
Purpose: named permission groups.
Key columns:
- id
- tenant_id
- role_code
- role_name
- description
- is_system

### permissions
Purpose: system capabilities.
Key columns:
- id
- tenant_id
- permission_code
- permission_name
- module_code

### user_roles
Purpose: user to role assignment.
Key columns:
- tenant_id
- user_id
- role_id
- assigned_at

### role_permissions
Purpose: role to permission assignment.
Key columns:
- tenant_id
- role_id
- permission_id
- granted_at

## 3. Organization And Structure

### org_units
Purpose: company, branch, department, store, workshop, cost-center-style structural nodes.
Key columns:
- id
- tenant_id
- parent_id
- unit_code
- unit_name
- unit_type
- is_active
- manager_user_id
Notes:
- Closure table used for hierarchy queries

### org_unit_closure
Purpose: ancestor-descendant traversal table.
Key columns:
- ancestor_id
- descendant_id
- depth
Notes:
- No soft delete
- Rebuilt only through hierarchy service

### user_org_units
Purpose: scope users to organization units.
Key columns:
- id
- tenant_id
- user_id
- org_unit_id
- membership_role
- is_primary

## 4. Party Master

### parties
Purpose: shared master for customer, supplier, employee, company, and person identity.
Key columns:
- id
- tenant_id
- party_code
- party_kind
- legal_name
- display_name
- tax_number
- registration_number
- status_code
Notes:
- This replaces separate customer and supplier master tables as primary identity records

### party_roles
Purpose: attach functional roles to a party.
Key columns:
- id
- tenant_id
- party_id
- role_code
- is_default
Examples of role_code:
- customer
- supplier
- employee
- vendor
- bank
- insurer

### party_contacts
Purpose: phones, emails, contact persons, messaging endpoints.
Key columns:
- id
- tenant_id
- party_id
- contact_type
- contact_value
- label
- is_primary
- is_verified

### addresses
Purpose: reusable address records.
Key columns:
- id
- tenant_id
- line_1
- line_2
- city
- state
- postal_code
- country_code

### party_addresses
Purpose: assign addresses to parties by role.
Key columns:
- id
- tenant_id
- party_id
- address_id
- address_role
- is_primary
Examples of address_role:
- billing
- shipping
- remittance
- registered_office

### tax_registrations
Purpose: tax registration per party and jurisdiction.
Key columns:
- id
- tenant_id
- party_id
- country_code
- tax_type
- registration_number
- valid_from
- valid_to
- is_primary

## 5. Product And Catalog

### currencies
Purpose: tenant currency catalog.
Key columns:
- id
- tenant_id
- code
- name
- symbol
- minor_units
- is_base_currency

### exchange_rates
Purpose: FX rates by day.
Key columns:
- id
- tenant_id
- base_currency_id
- quote_currency_id
- rate_date
- rate
- source_code

### uoms
Purpose: unit of measure master.
Key columns:
- id
- tenant_id
- code
- name
- category_code
- precision_scale

### uom_conversions
Purpose: conversion ratios between UOMs.
Key columns:
- id
- tenant_id
- from_uom_id
- to_uom_id
- multiplier
- divisor

### product_categories
Purpose: hierarchical product grouping.
Key columns:
- id
- tenant_id
- parent_id
- category_code
- category_name
- is_active

### products
Purpose: product family or sellable item root.
Key columns:
- id
- tenant_id
- product_category_id
- default_uom_id
- product_code
- product_name
- product_kind
- valuation_method
- tracking_mode
- is_stock_item
- is_sellable
- is_purchasable
Examples of product_kind:
- goods
- service
- bundle
- kit
- rental_asset

### product_variants
Purpose: sellable stock-keeping variant.
Key columns:
- id
- tenant_id
- product_id
- sku
- barcode
- variant_name
- status_code
- weight
- volume
- standard_cost

### product_identifiers
Purpose: multiple identifiers per product variant.
Key columns:
- id
- tenant_id
- product_variant_id
- identifier_type
- identifier_value
- is_primary
Examples of identifier_type:
- sku
- barcode
- qr
- rfid
- supplier_part_number

### price_lists
Purpose: named pricing policies.
Key columns:
- id
- tenant_id
- price_list_code
- price_list_name
- currency_id
- valid_from
- valid_to
- is_active

### price_list_items
Purpose: product variant prices in price lists.
Key columns:
- id
- tenant_id
- price_list_id
- product_variant_id
- min_quantity
- unit_price
- valid_from
- valid_to

## 6. Warehouse And Inventory

### warehouses
Purpose: physical or virtual stock facilities.
Key columns:
- id
- tenant_id
- org_unit_id
- warehouse_code
- warehouse_name
- warehouse_type
- address_id
- is_active
- is_virtual

### warehouse_locations
Purpose: internal stock locations.
Key columns:
- id
- tenant_id
- warehouse_id
- parent_id
- location_code
- location_name
- location_type
- is_pickable
- is_receivable
- is_storable

### warehouse_location_closure
Purpose: fast hierarchy traversal.
Key columns:
- ancestor_id
- descendant_id
- depth

### inventory_lots
Purpose: lot and batch identity.
Key columns:
- id
- tenant_id
- product_variant_id
- warehouse_id
- lot_code
- manufactured_at
- received_at
- expiry_date
- status_code

### inventory_serials
Purpose: serial-tracked units.
Key columns:
- id
- tenant_id
- product_variant_id
- warehouse_id
- lot_id
- serial_code
- status_code
- activated_at
- retired_at

### inventory_balances
Purpose: fast current snapshot for stock availability.
Key columns:
- id
- tenant_id
- product_variant_id
- warehouse_id
- location_id
- lot_id
- serial_id
- unit_of_measure_id
- qty_on_hand
- qty_reserved
- qty_available
- qty_damaged
- qty_in_transit
Notes:
- This is a snapshot table, not the source of historical truth

### stock_movements
Purpose: movement header.
Key columns:
- id
- tenant_id
- movement_number
- movement_type
- source_document_type
- source_document_id
- warehouse_id
- organization_unit_id
- status_code
- movement_at
- posted_by

### stock_movement_lines
Purpose: movement detail rows.
Key columns:
- id
- tenant_id
- stock_movement_id
- product_variant_id
- source_location_id
- destination_location_id
- lot_id
- serial_id
- unit_of_measure_id
- quantity
- unit_cost
- total_cost
- line_action
Notes:
- One of the fastest-growing tables in the system

### inventory_layers
Purpose: receipt and valuation layers.
Key columns:
- id
- tenant_id
- product_variant_id
- warehouse_id
- location_id
- lot_id
- serial_id
- unit_of_measure_id
- layer_type
- qty_received
- qty_remaining
- unit_cost
- total_cost
- source_type
- source_id
- received_at
Notes:
- Required for FIFO, FEFO, AVCO traceability, and audit

### inventory_layer_consumptions
Purpose: bridge consumed stock to source layers.
Key columns:
- id
- tenant_id
- inventory_layer_id
- stock_movement_line_id
- qty_consumed
- unit_cost
- total_cost

### stock_reservations
Purpose: reserve stock for demand.
Key columns:
- id
- tenant_id
- product_variant_id
- warehouse_id
- location_id
- lot_id
- serial_id
- source_type
- source_id
- source_line_id
- quantity_reserved
- reserved_at
- expires_at
- status_code

### stock_count_sessions
Purpose: physical count header.
Key columns:
- id
- tenant_id
- warehouse_id
- location_id
- count_number
- count_type
- status_code
- opened_by
- closed_by

### stock_count_lines
Purpose: physical count lines.
Key columns:
- id
- tenant_id
- stock_count_session_id
- product_variant_id
- location_id
- lot_id
- serial_id
- unit_of_measure_id
- system_quantity
- counted_quantity
- variance_quantity
- variance_reason

### inventory_adjustment_reasons
Purpose: standardized adjustment reason catalog.
Key columns:
- id
- tenant_id
- reason_code
- reason_name
- category_code
- is_system

## 7. Commercial Documents

### document_types
Purpose: metadata for allowed commercial document types.
Key columns:
- id
- tenant_id
- type_code
- type_name
- category_code
- affects_inventory
- affects_subledger
Examples:
- sales_order
- purchase_order
- shipment
- sales_invoice
- purchase_invoice
- sales_return
- purchase_return
- service_job
- service_invoice

### commercial_documents
Purpose: canonical document header.
Key columns:
- id
- tenant_id
- document_type_id
- party_id
- bill_to_party_id
- ship_to_party_id
- warehouse_id
- org_unit_id
- currency_id
- document_number
- external_reference
- status_code
- fulfillment_status_code
- document_date
- due_date
- posted_at
- cancelled_at
- subtotal_amount
- discount_amount
- tax_amount
- grand_total_amount
- cost_total_amount
Notes:
- Replaces duplicated order, invoice, shipment, and return header families

### commercial_document_lines
Purpose: canonical document lines.
Key columns:
- id
- tenant_id
- commercial_document_id
- line_no
- product_variant_id
- description
- unit_of_measure_id
- warehouse_id
- location_id
- ordered_quantity
- fulfilled_quantity
- invoiced_quantity
- unit_price
- discount_amount
- net_amount
- tax_amount
- gross_amount

### commercial_document_taxes
Purpose: line or header tax breakdown.
Key columns:
- id
- tenant_id
- commercial_document_line_id
- tax_code
- tax_rate
- taxable_amount
- tax_amount

### document_links
Purpose: explicit links between related business documents.
Key columns:
- id
- tenant_id
- source_document_id
- target_document_id
- link_type
Examples of link_type:
- derived_from
- fulfills
- invoices
- returns
- settles

### document_status_history
Purpose: immutable lifecycle transitions.
Key columns:
- id
- tenant_id
- commercial_document_id
- from_status_code
- to_status_code
- changed_by
- changed_at
- reason_text

## 8. Finance And Subledger

### accounts
Purpose: chart of accounts.
Key columns:
- id
- tenant_id
- parent_id
- account_code
- account_name
- account_type
- account_subtype
- normal_balance
- is_control_account
- is_active

### fiscal_years
Purpose: fiscal calendar years.
Key columns:
- id
- tenant_id
- year_name
- start_date
- end_date
- status_code

### fiscal_periods
Purpose: fiscal posting periods.
Key columns:
- id
- tenant_id
- fiscal_year_id
- period_number
- period_name
- start_date
- end_date
- status_code

### journal_entries
Purpose: accounting header.
Key columns:
- id
- tenant_id
- fiscal_period_id
- entry_number
- entry_type
- reference_type
- reference_id
- description
- entry_date
- posting_date
- status_code
- reversal_entry_id
- created_by
- posted_by
- posted_at
Notes:
- Append-only once posted

### journal_lines
Purpose: accounting detail.
Key columns:
- id
- tenant_id
- journal_entry_id
- line_no
- account_id
- party_id
- org_unit_id
- currency_id
- exchange_rate
- debit_amount
- credit_amount
- base_debit_amount
- base_credit_amount
- cost_center_org_unit_id

### subledger_documents
Purpose: AR and AP style open-item records.
Key columns:
- id
- tenant_id
- subledger_type
- party_id
- source_document_id
- document_number
- document_date
- due_date
- original_amount
- open_amount
- currency_id
- status_code
Examples of subledger_type:
- receivable
- payable
- debit_note
- credit_note

### subledger_allocations
Purpose: allocations between subledger items and payments or offsets.
Key columns:
- id
- tenant_id
- subledger_document_id
- allocation_type
- reference_type
- reference_id
- allocated_amount
- allocated_at

### payments
Purpose: inbound and outbound payments.
Key columns:
- id
- tenant_id
- party_id
- currency_id
- bank_account_id
- payment_number
- payment_direction
- payment_method
- status_code
- payment_date
- amount
- reference_number
- journal_entry_id
- idempotency_key

### payment_allocations
Purpose: allocate payments to documents.
Key columns:
- id
- tenant_id
- payment_id
- commercial_document_id
- subledger_document_id
- journal_entry_id
- amount
- allocated_at

### bank_accounts
Purpose: company bank and cash-equivalent accounts.
Key columns:
- id
- tenant_id
- account_id
- currency_id
- bank_code
- bank_name
- account_name
- account_number_masked
- iban
- status_code

### bank_transactions
Purpose: imported or synced bank statement lines.
Key columns:
- id
- tenant_id
- bank_account_id
- external_reference
- transaction_date
- value_date
- description
- amount
- balance_after
- direction_code
- reconciliation_status_code
Notes:
- Large table, partition-ready

### bank_reconciliations
Purpose: reconciliation run header.
Key columns:
- id
- tenant_id
- bank_account_id
- period_start
- period_end
- opening_balance
- closing_balance
- status_code
- completed_by
- completed_at

## 9. Audit, Attachments, And Integration

### attachments
Purpose: generic file references for supported entities.
Key columns:
- id
- tenant_id
- attachable_type
- attachable_id
- uploaded_by
- disk
- path
- original_name
- mime_type
- size_bytes
- checksum

### audit_logs
Purpose: immutable business and security audit trail.
Key columns:
- id
- tenant_id
- user_id
- auditable_type
- auditable_id
- action_code
- event_name
- summary
- old_values_json
- new_values_json
- ip_address
- user_agent
- context_json
- occurred_at
Notes:
- Do not soft delete
- Archive by time window

### integration_outbox
Purpose: reliable outbound event delivery.
Key columns:
- id
- tenant_id
- event_name
- aggregate_type
- aggregate_id
- payload_json
- status_code
- available_at
- processed_at
- attempts
- last_error
Notes:
- Cleanup after successful dispatch retention period

### integration_inbox
Purpose: idempotent inbound integration handling.
Key columns:
- id
- tenant_id
- source_system
- message_key
- payload_json
- received_at
- processed_at
- status_code
- error_message

## Permanent Vs Archive Guidance

Permanent or near-permanent:
- tenants
- users
- org_units
- parties
- products
- product_variants
- accounts
- fiscal_years
- fiscal_periods

Archive-capable transactional:
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

Aggressive cleanup candidates:
- audit_logs
- integration_outbox
- integration_inbox
- document_status_history

This catalog is the reference for the new schema. Application modules should map to these tables instead of introducing parallel master or transaction tables.