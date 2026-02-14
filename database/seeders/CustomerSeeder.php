<?php

namespace Database\Seeders;

use App\Modules\Customer\Models\Customer;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createCustomersForTenant($tenant);
        }

        $this->command->info('Customers created successfully for all tenants!');
    }

    private function createCustomersForTenant(Tenant $tenant): void
    {
        $customers = [
            // Individual customers
            [
                'name' => 'John Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+1-555-0101',
                'mobile' => '+1-555-0102',
                'customer_type' => 'individual',
                'address' => '123 Oak Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '62701',
                'credit_limit' => 5000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Emily Johnson',
                'email' => 'emily.j@email.com',
                'phone' => '+1-555-0201',
                'mobile' => '+1-555-0202',
                'customer_type' => 'individual',
                'address' => '456 Maple Avenue',
                'city' => 'Portland',
                'state' => 'OR',
                'country' => 'USA',
                'postal_code' => '97201',
                'credit_limit' => 3000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'mbrown@email.com',
                'phone' => '+1-555-0301',
                'customer_type' => 'individual',
                'address' => '789 Pine Road',
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'USA',
                'postal_code' => '73301',
                'credit_limit' => 2500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Davis',
                'email' => 'sarah.davis@email.com',
                'phone' => '+1-555-0401',
                'mobile' => '+1-555-0402',
                'customer_type' => 'individual',
                'address' => '321 Elm Street',
                'city' => 'Seattle',
                'state' => 'WA',
                'country' => 'USA',
                'postal_code' => '98101',
                'credit_limit' => 4000.00,
                'is_active' => true,
            ],
            // Business customers
            [
                'name' => 'TechCorp Solutions',
                'email' => 'billing@techcorp.com',
                'phone' => '+1-555-1001',
                'customer_type' => 'business',
                'address' => '1000 Business Park Drive',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '94102',
                'credit_limit' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Global Industries Inc',
                'email' => 'accounts@globalindustries.com',
                'phone' => '+1-555-1101',
                'customer_type' => 'business',
                'address' => '2500 Corporate Center',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'credit_limit' => 75000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Prime Retail Group',
                'email' => 'finance@primeretail.com',
                'phone' => '+1-555-1201',
                'customer_type' => 'retail',
                'address' => '3000 Shopping Plaza',
                'city' => 'Miami',
                'state' => 'FL',
                'country' => 'USA',
                'postal_code' => '33101',
                'credit_limit' => 40000.00,
                'is_active' => true,
            ],
            // Wholesale customers
            [
                'name' => 'Wholesale Distributors LLC',
                'email' => 'orders@wholesaledist.com',
                'phone' => '+1-555-1301',
                'customer_type' => 'wholesale',
                'address' => '4000 Distribution Way',
                'city' => 'Dallas',
                'state' => 'TX',
                'country' => 'USA',
                'postal_code' => '75201',
                'credit_limit' => 100000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Metro Supply Chain',
                'email' => 'procurement@metrosupply.com',
                'phone' => '+1-555-1401',
                'customer_type' => 'wholesale',
                'address' => '5000 Logistics Hub',
                'city' => 'Atlanta',
                'state' => 'GA',
                'country' => 'USA',
                'postal_code' => '30301',
                'credit_limit' => 80000.00,
                'is_active' => true,
            ],
            // Government customer
            [
                'name' => 'City of Springfield',
                'email' => 'purchasing@springfield.gov',
                'phone' => '+1-555-1501',
                'customer_type' => 'government',
                'address' => '100 City Hall Plaza',
                'city' => 'Springfield',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '62701',
                'credit_limit' => 150000.00,
                'is_active' => true,
            ],
            // Additional individual customers
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@email.com',
                'phone' => '+1-555-0501',
                'customer_type' => 'individual',
                'address' => '654 Cedar Lane',
                'city' => 'Denver',
                'state' => 'CO',
                'country' => 'USA',
                'postal_code' => '80201',
                'credit_limit' => 3500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Jennifer Martinez',
                'email' => 'j.martinez@email.com',
                'phone' => '+1-555-0601',
                'mobile' => '+1-555-0602',
                'customer_type' => 'individual',
                'address' => '987 Birch Drive',
                'city' => 'Phoenix',
                'state' => 'AZ',
                'country' => 'USA',
                'postal_code' => '85001',
                'credit_limit' => 2000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Robert Garcia',
                'email' => 'robert.g@email.com',
                'phone' => '+1-555-0701',
                'customer_type' => 'individual',
                'address' => '147 Willow Court',
                'city' => 'Boston',
                'state' => 'MA',
                'country' => 'USA',
                'postal_code' => '02101',
                'credit_limit' => 4500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@email.com',
                'phone' => '+1-555-0801',
                'mobile' => '+1-555-0802',
                'customer_type' => 'individual',
                'address' => '258 Spruce Street',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'country' => 'USA',
                'postal_code' => '19101',
                'credit_limit' => 3000.00,
                'is_active' => false,
            ],
            [
                'name' => 'James Taylor',
                'email' => 'jtaylor@email.com',
                'phone' => '+1-555-0901',
                'customer_type' => 'individual',
                'address' => '369 Ash Boulevard',
                'city' => 'San Diego',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '92101',
                'credit_limit' => 5500.00,
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email' => $customerData['email'],
                ],
                array_merge($customerData, ['tenant_id' => $tenant->id])
            );
        }
    }
}
