<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\Organization;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\Permission;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Models\Unit;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Lead;
use Modules\Purchase\Models\Vendor;
use Modules\Inventory\Models\Warehouse;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Enums\FiscalPeriodStatus;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('❌ Demo seeder cannot be run in production environment!');
            return;
        }

        $this->command->info('🌱 Seeding demo data...');
        $this->command->newLine();

        DB::transaction(function () {
            // 1. Create Tenants and Organizations
            $this->command->info('📊 Creating tenants and organizations...');
            $tenant = $this->createTenant();
            $organization = $this->createOrganizations($tenant);

            // 2. Create Users and Roles
            $this->command->info('👥 Creating users and roles...');
            $users = $this->createUsersAndRoles($tenant, $organization);

            // 3. Create Product Categories and Units
            $this->command->info('📦 Creating product catalog...');
            $units = $this->createUnits($tenant);
            $categories = $this->createProductCategories($tenant);
            $products = $this->createProducts($tenant, $categories, $units);

            // 4. Create Customers and Leads
            $this->command->info('🤝 Creating customers and leads...');
            $customers = $this->createCustomers($tenant);
            $leads = $this->createLeads($tenant);

            // 5. Create Vendors
            $this->command->info('🏭 Creating vendors...');
            $vendors = $this->createVendors($tenant);

            // 6. Create Warehouses
            $this->command->info('🏢 Creating warehouses...');
            $warehouses = $this->createWarehouses($tenant);

            // 7. Create Chart of Accounts
            $this->command->info('💰 Creating chart of accounts...');
            $fiscalYear = $this->createFiscalYear($tenant);
            $accounts = $this->createChartOfAccounts($tenant);

            $this->command->newLine();
            $this->command->info('✅ Demo data seeded successfully!');
            $this->displaySummary($tenant, $organization, $users, $products, $customers, $vendors);
        });
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Demo Corporation',
            'slug' => 'demo-corp',
            'domain' => 'demo.example.com',
            'database' => 'demo_corp',
            'is_active' => true,
            'settings' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
                'language' => 'en',
            ],
        ]);
    }

    private function createOrganizations(Tenant $tenant): Organization
    {
        $root = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Corporation HQ',
            'code' => 'HQ',
            'parent_id' => null,
            'is_active' => true,
        ]);

        // Create child organizations
        $salesOrg = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sales Department',
            'code' => 'SALES',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        $opsOrg = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations Department',
            'code' => 'OPS',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        return $root;
    }

    private function createUsersAndRoles(Tenant $tenant, Organization $organization): array
    {
        // Create Roles
        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system access',
        ]);

        $managerRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Department manager',
        ]);

        $userRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Standard user',
        ]);

        // Create Users
        $password = Hash::make(env('SEED_ADMIN_PASSWORD', 'password'));

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Admin User',
            'email' => 'admin@demo.example.com',
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Manager User',
            'email' => 'manager@demo.example.com',
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $manager->roles()->attach($managerRole);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Standard User',
            'email' => 'user@demo.example.com',
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($userRole);

        return [$admin, $manager, $user];
    }

    private function createUnits(Tenant $tenant): array
    {
        $units = [
            ['name' => 'Piece', 'symbol' => 'pc', 'type' => 'quantity'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight'],
            ['name' => 'Meter', 'symbol' => 'm', 'type' => 'length'],
            ['name' => 'Liter', 'symbol' => 'L', 'type' => 'volume'],
            ['name' => 'Box', 'symbol' => 'box', 'type' => 'quantity'],
            ['name' => 'Dozen', 'symbol' => 'dz', 'type' => 'quantity'],
        ];

        $created = [];
        foreach ($units as $unit) {
            $created[] = Unit::create([
                'tenant_id' => $tenant->id,
                'name' => $unit['name'],
                'symbol' => $unit['symbol'],
                'type' => $unit['type'],
                'is_active' => true,
            ]);
        }

        return $created;
    }

    private function createProductCategories(Tenant $tenant): array
    {
        $categories = [
            'Electronics',
            'Furniture',
            'Office Supplies',
            'Software',
            'Hardware',
        ];

        $created = [];
        foreach ($categories as $category) {
            $created[] = ProductCategory::create([
                'tenant_id' => $tenant->id,
                'name' => $category,
                'code' => strtoupper(substr($category, 0, 3)),
                'is_active' => true,
            ]);
        }

        return $created;
    }

    private function createProducts(Tenant $tenant, array $categories, array $units): array
    {
        $products = [
            ['name' => 'Laptop Computer', 'category' => 0, 'unit' => 0, 'price' => 999.99],
            ['name' => 'Office Desk', 'category' => 1, 'unit' => 0, 'price' => 299.99],
            ['name' => 'Printer Paper (A4)', 'category' => 2, 'unit' => 4, 'price' => 24.99],
            ['name' => 'CRM Software License', 'category' => 3, 'unit' => 0, 'price' => 49.99],
            ['name' => 'Network Switch', 'category' => 4, 'unit' => 0, 'price' => 149.99],
            ['name' => 'Office Chair', 'category' => 1, 'unit' => 0, 'price' => 199.99],
            ['name' => 'USB Cable', 'category' => 4, 'unit' => 0, 'price' => 9.99],
            ['name' => 'Whiteboard', 'category' => 2, 'unit' => 0, 'price' => 79.99],
        ];

        $created = [];
        foreach ($products as $index => $product) {
            $created[] = Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories[$product['category']]->id,
                'name' => $product['name'],
                'code' => 'PROD-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'description' => 'Demo product: ' . $product['name'],
                'type' => 'product',
                'buying_unit_id' => $units[$product['unit']]->id,
                'selling_unit_id' => $units[$product['unit']]->id,
                'standard_cost' => bcmul((string)$product['price'], '0.7', 2),
                'is_active' => true,
            ]);
        }

        return $created;
    }

    private function createCustomers(Tenant $tenant): array
    {
        $customers = [
            ['name' => 'Acme Corporation', 'email' => 'contact@acme.example.com'],
            ['name' => 'TechStart Inc', 'email' => 'hello@techstart.example.com'],
            ['name' => 'Global Solutions Ltd', 'email' => 'info@globalsolutions.example.com'],
            ['name' => 'Innovative Systems', 'email' => 'sales@innovativesystems.example.com'],
            ['name' => 'Enterprise Partners', 'email' => 'partners@enterprise.example.com'],
        ];

        $created = [];
        foreach ($customers as $index => $customer) {
            $created[] = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $customer['name'],
                'code' => 'CUST-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'email' => $customer['email'],
                'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                'status' => 'active',
                'type' => 'business',
            ]);
        }

        return $created;
    }

    private function createLeads(Tenant $tenant): array
    {
        $leads = [
            ['name' => 'John Doe', 'company' => 'Startup Ventures', 'status' => 'new'],
            ['name' => 'Jane Smith', 'company' => 'Tech Innovators', 'status' => 'qualified'],
            ['name' => 'Bob Johnson', 'company' => 'Business Solutions', 'status' => 'contacted'],
        ];

        $created = [];
        foreach ($leads as $index => $lead) {
            $created[] = Lead::create([
                'tenant_id' => $tenant->id,
                'name' => $lead['name'],
                'code' => 'LEAD-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $lead['name'])) . '@' . strtolower(str_replace(' ', '', $lead['company'])) . '.example.com',
                'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                'company' => $lead['company'],
                'status' => $lead['status'],
                'source' => 'website',
            ]);
        }

        return $created;
    }

    private function createVendors(Tenant $tenant): array
    {
        $vendors = [
            ['name' => 'Office Supplies Co', 'email' => 'sales@officesupplies.example.com'],
            ['name' => 'Tech Hardware Distributors', 'email' => 'orders@techhardware.example.com'],
            ['name' => 'Furniture Wholesale', 'email' => 'info@furniturewholesale.example.com'],
        ];

        $created = [];
        foreach ($vendors as $index => $vendor) {
            $created[] = Vendor::create([
                'tenant_id' => $tenant->id,
                'name' => $vendor['name'],
                'code' => 'VEND-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'email' => $vendor['email'],
                'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                'status' => 'active',
            ]);
        }

        return $created;
    }

    private function createWarehouses(Tenant $tenant): array
    {
        $warehouses = [
            ['name' => 'Main Warehouse', 'code' => 'WH-MAIN'],
            ['name' => 'Secondary Warehouse', 'code' => 'WH-SEC'],
        ];

        $created = [];
        foreach ($warehouses as $warehouse) {
            $created[] = Warehouse::create([
                'tenant_id' => $tenant->id,
                'name' => $warehouse['name'],
                'code' => $warehouse['code'],
                'address' => '123 Industrial Blvd',
                'city' => 'Demo City',
                'country' => 'US',
                'status' => 'active',
            ]);
        }

        return $created;
    }

    private function createFiscalYear(Tenant $tenant): FiscalYear
    {
        $fiscalYear = FiscalYear::create([
            'tenant_id' => $tenant->id,
            'name' => now()->year . ' Fiscal Year',
            'code' => 'FY' . now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        // Create fiscal periods (months)
        for ($month = 1; $month <= 12; $month++) {
            $start = now()->startOfYear()->addMonths($month - 1);
            $end = $start->copy()->endOfMonth();

            FiscalPeriod::create([
                'tenant_id' => $tenant->id,
                'fiscal_year_id' => $fiscalYear->id,
                'name' => $start->format('F Y'),
                'code' => $start->format('Y-m'),
                'start_date' => $start,
                'end_date' => $end,
                'status' => $month < now()->month ? FiscalPeriodStatus::CLOSED->value : FiscalPeriodStatus::OPEN->value,
            ]);
        }

        return $fiscalYear;
    }

    private function createChartOfAccounts(Tenant $tenant): array
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Cash', 'type' => AccountType::ASSET],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => AccountType::ASSET],
            ['code' => '1200', 'name' => 'Inventory', 'type' => AccountType::ASSET],
            ['code' => '1500', 'name' => 'Fixed Assets', 'type' => AccountType::ASSET],

            // Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => AccountType::LIABILITY],
            ['code' => '2100', 'name' => 'Short-term Loans', 'type' => AccountType::LIABILITY],

            // Equity
            ['code' => '3000', 'name' => 'Owner\'s Equity', 'type' => AccountType::EQUITY],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => AccountType::EQUITY],

            // Revenue
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => AccountType::REVENUE],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => AccountType::REVENUE],

            // Expenses
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => AccountType::EXPENSE],
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => AccountType::EXPENSE],
            ['code' => '6100', 'name' => 'Salaries and Wages', 'type' => AccountType::EXPENSE],
            ['code' => '6200', 'name' => 'Rent Expense', 'type' => AccountType::EXPENSE],
        ];

        $created = [];
        foreach ($accounts as $account) {
            $created[] = Account::create([
                'tenant_id' => $tenant->id,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type']->value,
                'parent_id' => null,
                'status' => 'active',
            ]);
        }

        return $created;
    }

    private function displaySummary(
        Tenant $tenant,
        Organization $organization,
        array $users,
        array $products,
        array $customers,
        array $vendors
    ): void {
        $this->command->newLine();
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Tenants', 1],
                ['Organizations', Organization::where('tenant_id', $tenant->id)->count()],
                ['Users', count($users)],
                ['Products', count($products)],
                ['Product Categories', ProductCategory::where('tenant_id', $tenant->id)->count()],
                ['Units', Unit::where('tenant_id', $tenant->id)->count()],
                ['Customers', count($customers)],
                ['Leads', Lead::where('tenant_id', $tenant->id)->count()],
                ['Vendors', count($vendors)],
                ['Warehouses', Warehouse::where('tenant_id', $tenant->id)->count()],
                ['Chart of Accounts', Account::where('tenant_id', $tenant->id)->count()],
                ['Fiscal Periods', FiscalPeriod::where('tenant_id', $tenant->id)->count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('📧 Demo User Credentials:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@demo.example.com', env('SEED_ADMIN_PASSWORD', 'password')],
                ['Manager', 'manager@demo.example.com', env('SEED_ADMIN_PASSWORD', 'password')],
                ['User', 'user@demo.example.com', env('SEED_ADMIN_PASSWORD', 'password')],
            ]
        );
    }
}
