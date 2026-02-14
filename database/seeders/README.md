# Database Seeders Documentation

This document provides information about the database seeders available in the AutoERP system.

## Overview

The seeders create realistic demo data for development and testing purposes. They populate the database with tenants, users, roles, permissions, branches, customers, and products.

## Running Seeders

### Run All Seeders

To run all seeders at once (recommended for initial setup):

```bash
php artisan migrate:fresh --seed
```

### Run Specific Seeders

To run a specific seeder:

```bash
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=BranchSeeder
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=ProductSeeder
```

## Seeder Execution Order

The seeders must be executed in the following order due to foreign key constraints:

1. **TenantSeeder** - Creates tenants (required by all other seeders)
2. **RolePermissionSeeder** - Creates roles and permissions
3. **UserSeeder** - Creates users and assigns roles
4. **BranchSeeder** - Creates organizational branches
5. **CustomerSeeder** - Creates customers
6. **ProductSeeder** - Creates products

## Seeded Data

### Tenants (3 tenants)

| Subdomain    | Name              | Currency | Timezone              | Status |
|--------------|-------------------|----------|-----------------------|--------|
| acme         | Acme Corp         | USD      | America/New_York      | Active |
| techstart    | TechStart Inc     | USD      | America/Los_Angeles   | Active |
| globaltrade  | Global Trade Ltd  | GBP      | Europe/London         | Active |

### Users (6 users per tenant = 18 total)

Each tenant has the following user accounts:

| Email Pattern              | Role               | Password |
|---------------------------|--------------------|----------|
| admin@{subdomain}.com     | Super Admin        | password |
| manager@{subdomain}.com   | Manager            | password |
| sales@{subdomain}.com     | Sales              | password |
| inventory@{subdomain}.com | Inventory Manager  | password |
| cashier@{subdomain}.com   | Cashier            | password |
| viewer@{subdomain}.com    | Viewer             | password |

**Example credentials:**
- Email: `admin@acme.com`
- Password: `password`

### Roles (7 roles per tenant = 21 total)

1. **Super Admin** - Full access to all modules
2. **Tenant Admin** - Administrative access to tenant-specific data
3. **Manager** - Management-level access to most modules
4. **Sales** - Access to customers, products, POS, invoices, leads, opportunities
5. **Inventory Manager** - Access to products, inventory, warehouses, vendors
6. **Cashier** - Access to POS and payment operations
7. **Viewer** - Read-only access to key modules

### Permissions (73 permissions per tenant = 219 total)

Permissions are created for the following modules:
- Users (view, create, edit, delete)
- Branches (view, create, edit, delete)
- Customers (view, create, edit, delete)
- Vendors (view, create, edit, delete)
- Products (view, create, edit, delete)
- Inventory (view, create, edit, delete, transfer, adjust)
- Warehouses (view, create, edit, delete)
- POS (view, create, refund, void)
- Invoices (view, create, edit, delete, approve, send)
- Payments (view, create, edit, delete)
- Leads (view, create, edit, delete, convert)
- Opportunities (view, create, edit, delete, close)
- Campaigns (view, create, edit, delete)
- Fleet (view, create, edit, delete)
- Maintenance (view, create, edit, delete, schedule)
- Reports (view, export)
- Settings (view, edit)
- Audit Logs (view, export)

### Branches (4 branches per tenant = 12 total)

Each tenant has:
1. **Headquarters** - Main office (parent branch)
2. **Branch A** - Regional branch
3. **Branch B** - Retail location
4. **Main Warehouse** - Storage facility

### Customers (15 customers per tenant = 45 total)

Customer types include:
- **Individual** (4 customers) - Personal customers
- **Business** (3 customers) - Corporate clients
- **Retail** (1 customer) - Retail chain
- **Wholesale** (2 customers) - Wholesale distributors
- **Government** (1 customer) - Government entity
- **Additional individuals** (4 customers)

Each customer has realistic data including:
- Name, email, phone
- Address (city, state, country, postal code)
- Credit limit
- Customer type
- Active status

### Products (20 products per tenant = 60 total)

Product categories include:

#### Electronics (10 products)
- Laptop, Monitor, Mouse, Keyboard, Printer
- Tablet, Webcam, Headset, USB Cable, USB Hub

#### Office Supplies (5 products)
- Copy Paper, Pens, Notebooks, Stapler, Folders

#### Furniture (3 products)
- Office Desk, Office Chair, Filing Cabinet

#### Services (2 products)
- Installation Service (hourly)
- Maintenance Service (yearly)

Each product includes:
- Unique SKU per tenant
- Barcode (where applicable)
- Description
- Category
- Unit of measure
- Cost and selling prices
- Reorder levels
- Active status
- Inventory tracking flag

## Multi-Tenant Architecture

All seeders are designed to work with the multi-tenant architecture:

- Each tenant's data is completely isolated
- Roles and permissions are tenant-specific
- Users can only access data from their own tenant
- All foreign keys include `tenant_id` for proper scoping

## Idempotency

All seeders use `firstOrCreate()` to ensure they can be run multiple times without creating duplicate records. This makes them safe to re-run during development.

## Data Statistics

Total records created:
- **Tenants**: 3
- **Users**: 18
- **Roles**: 21
- **Permissions**: 219
- **Branches**: 12
- **Customers**: 45
- **Products**: 60

## Customization

To customize the seeded data:

1. Edit the respective seeder file in `database/seeders/`
2. Modify the data arrays within each seeder
3. Run the seeder again: `php artisan db:seed --class=SeederName`

## Testing with Seeded Data

After seeding, you can test the system by:

1. Logging in with any user credentials (see Users table above)
2. Switching between tenants using subdomains (e.g., `acme.example.com`)
3. Testing role-based access control with different user roles
4. Creating transactions using the seeded customers and products

## Troubleshooting

### Foreign Key Constraint Errors

If you encounter foreign key errors, ensure you're running seeders in the correct order:
```bash
php artisan migrate:fresh --seed
```

### Duplicate Entry Errors

Seeders use `firstOrCreate()` which should prevent duplicates. If you still get errors:
```bash
php artisan migrate:fresh --seed
```

### Permission Errors

If permission-related errors occur:
```bash
php artisan cache:clear
php artisan config:clear
php artisan db:seed --class=RolePermissionSeeder
```

## Production Warning

⚠️ **WARNING**: These seeders are intended for development and testing only. Do not run them in production environments as they create accounts with default passwords and may overwrite existing data.

## Additional Resources

- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Seeding Documentation](https://laravel.com/docs/seeding)
