# Database Schema Documentation

## Overview

This document describes the database schema for the Modular SaaS Vehicle Service application.

## Multi-Tenancy

All tables include a `tenant_id` field for tenant isolation. A global scope is applied to all queries to ensure data is filtered by the current tenant.

## Core Tables

### tenants
- `id`: Primary key
- `name`: Tenant/organization name
- `subdomain`: Unique subdomain
- `database_name`: Optional separate database
- `settings`: JSON configuration
- `status`: Active, suspended, etc.
- `created_at`, `updated_at`

### users
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: Full name
- `email`: Unique email
- `password`: Hashed password
- `role_id`: Foreign key to roles
- `created_at`, `updated_at`, `deleted_at`

## Customer Module

### customers
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `first_name`: Customer first name
- `last_name`: Customer last name
- `email`: Unique email
- `phone`: Contact number
- `address`, `city`, `state`, `zip_code`, `country`: Address fields
- `preferences`: JSON for customer preferences
- `created_at`, `updated_at`, `deleted_at`

Indexes:
- `tenant_id`
- `email`
- `phone`
- `(tenant_id, email)`
- `(first_name, last_name)`

## Vehicle Module

### vehicles
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `customer_id`: Foreign key to customers
- `vin`: Vehicle Identification Number (unique)
- `registration_number`: License plate (unique)
- `make`: Manufacturer
- `model`: Model name
- `year`: Manufacturing year
- `color`: Vehicle color
- `engine_number`: Engine identification
- `current_mileage`: Current odometer reading
- `fuel_type`: Petrol, diesel, electric, hybrid
- `transmission_type`: Manual, automatic, CVT
- `specifications`: JSON for additional specs
- `purchase_date`: Date of purchase
- `next_service_date`: Scheduled service date
- `next_service_mileage`: Mileage for next service
- `created_at`, `updated_at`, `deleted_at`

Indexes:
- `tenant_id`
- `customer_id`
- `vin`
- `registration_number`
- `(tenant_id, customer_id)`

### vehicle_ownership_history
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `vehicle_id`: Foreign key to vehicles
- `from_customer_id`: Previous owner
- `to_customer_id`: New owner
- `transfer_date`: Date of transfer
- `notes`: Transfer notes
- `created_at`, `updated_at`

### meter_readings
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `vehicle_id`: Foreign key to vehicles
- `mileage`: Odometer reading
- `recorded_at`: Timestamp of reading
- `recorded_by`: User ID who recorded
- `notes`: Optional notes
- `created_at`, `updated_at`

## Branch Module

### branches
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: Branch name
- `code`: Unique branch code
- `address`, `city`, `state`, `zip_code`, `country`: Address fields
- `phone`: Contact number
- `email`: Branch email
- `manager_id`: Foreign key to users
- `settings`: JSON for branch-specific settings
- `status`: Active, inactive
- `created_at`, `updated_at`, `deleted_at`

### service_bays
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `branch_id`: Foreign key to branches
- `name`: Bay name/number
- `capacity`: Number of vehicles
- `status`: Available, occupied, maintenance
- `created_at`, `updated_at`

## Appointment Module

### appointments
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `customer_id`: Foreign key to customers
- `vehicle_id`: Foreign key to vehicles
- `branch_id`: Foreign key to branches
- `service_bay_id`: Foreign key to service_bays
- `appointment_date`: Scheduled date
- `appointment_time`: Scheduled time
- `estimated_duration`: Minutes
- `status`: Scheduled, confirmed, in_progress, completed, cancelled
- `notes`: Appointment notes
- `created_at`, `updated_at`, `deleted_at`

## Job Card Module

### job_cards
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `job_number`: Unique job identifier
- `customer_id`: Foreign key to customers
- `vehicle_id`: Foreign key to vehicles
- `branch_id`: Foreign key to branches
- `appointment_id`: Optional foreign key to appointments
- `assigned_to`: User ID of technician
- `status`: Created, in_progress, completed, invoiced
- `priority`: Low, normal, high, urgent
- `start_date`: Job start date
- `completion_date`: Job completion date
- `notes`: Job notes
- `created_at`, `updated_at`, `deleted_at`

### job_card_items
- `id`: Primary key
- `job_card_id`: Foreign key to job_cards
- `item_type`: Service, part
- `item_id`: Foreign key to services or inventory_items
- `description`: Item description
- `quantity`: Quantity
- `unit_price`: Price per unit
- `total_price`: Total price
- `status`: Pending, completed
- `created_at`, `updated_at`

## Inventory Module

### inventory_items
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `sku`: Stock keeping unit
- `name`: Item name
- `description`: Item description
- `category_id`: Foreign key to categories
- `unit_price`: Price per unit
- `cost_price`: Cost per unit
- `quantity_in_stock`: Current stock level
- `minimum_stock_level`: Reorder point
- `is_dummy_item`: Boolean for dummy items
- `created_at`, `updated_at`, `deleted_at`

### stock_movements
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `item_id`: Foreign key to inventory_items
- `branch_id`: Foreign key to branches
- `movement_type`: In, out, adjustment, transfer
- `quantity`: Movement quantity
- `reference_type`: Job card, purchase order, etc.
- `reference_id`: ID of reference
- `notes`: Movement notes
- `created_by`: User ID
- `created_at`, `updated_at`

## Invoice Module

### invoices
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `invoice_number`: Unique invoice number
- `customer_id`: Foreign key to customers
- `job_card_id`: Foreign key to job_cards
- `subtotal`: Subtotal amount
- `tax_amount`: Tax amount
- `discount_amount`: Discount amount
- `total_amount`: Total amount
- `status`: Draft, sent, paid, overdue, cancelled
- `issued_date`: Invoice issue date
- `due_date`: Payment due date
- `paid_date`: Actual payment date
- `notes`: Invoice notes
- `created_at`, `updated_at`, `deleted_at`

### payments
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `invoice_id`: Foreign key to invoices
- `amount`: Payment amount
- `payment_method`: Cash, card, bank transfer, etc.
- `payment_date`: Date of payment
- `reference_number`: Transaction reference
- `notes`: Payment notes
- `created_by`: User ID
- `created_at`, `updated_at`

## Relationships

### One-to-Many
- Tenant → Users, Customers, Vehicles, Branches, etc.
- Customer → Vehicles, Appointments, Job Cards, Invoices
- Vehicle → Appointments, Job Cards, Meter Readings
- Branch → Service Bays, Appointments, Job Cards
- Job Card → Job Card Items

### Many-to-Many
- Users ↔ Roles (via role_user)
- Users ↔ Permissions (via permission_user)
- Roles ↔ Permissions (via permission_role)

## Indexing Strategy

All tables include:
- Primary key index
- `tenant_id` index
- Foreign key indexes
- Composite indexes for common queries
- Unique indexes for business constraints

## Data Integrity

- Foreign key constraints for referential integrity
- Check constraints for valid enum values
- NOT NULL constraints for required fields
- Default values for status fields
- Soft deletes for most entities
