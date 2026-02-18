<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Procurement\Entities\Supplier;

/**
 * Procurement Database Seeder
 *
 * Seeds sample suppliers for the Procurement module.
 */
class ProcurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample suppliers
        $suppliers = [
            [
                'tenant_id' => 1,
                'code' => 'SUP-00001',
                'name' => 'Tech Solutions Inc.',
                'email' => 'sales@techsolutions.com',
                'phone' => '+1-555-0101',
                'mobile' => '+1-555-0102',
                'website' => 'https://techsolutions.com',
                'tax_id' => 'TAX-12345678',
                'payment_terms' => 'Net 30',
                'credit_limit' => 100000.00,
                'currency' => 'USD',
                'rating' => 4.5,
                'status' => 'active',
                'notes' => 'Primary IT equipment supplier',
            ],
            [
                'tenant_id' => 1,
                'code' => 'SUP-00002',
                'name' => 'Office Supplies Co.',
                'email' => 'orders@officesupplies.com',
                'phone' => '+1-555-0201',
                'mobile' => '+1-555-0202',
                'website' => 'https://officesupplies.com',
                'tax_id' => 'TAX-23456789',
                'payment_terms' => 'Net 60',
                'credit_limit' => 50000.00,
                'currency' => 'USD',
                'rating' => 4.0,
                'status' => 'active',
                'notes' => 'Office supplies and furniture',
            ],
            [
                'tenant_id' => 1,
                'code' => 'SUP-00003',
                'name' => 'Global Manufacturing Ltd.',
                'email' => 'procurement@globalmanufacturing.com',
                'phone' => '+44-20-1234-5678',
                'mobile' => '+44-79-1234-5678',
                'website' => 'https://globalmanufacturing.com',
                'tax_id' => 'VAT-GB123456789',
                'payment_terms' => '2/10 Net 30',
                'credit_limit' => 500000.00,
                'currency' => 'GBP',
                'rating' => 4.8,
                'status' => 'active',
                'notes' => 'Manufacturing parts and components',
            ],
            [
                'tenant_id' => 1,
                'code' => 'SUP-00004',
                'name' => 'Express Logistics Services',
                'email' => 'bookings@expresslogistics.com',
                'phone' => '+1-555-0301',
                'mobile' => '+1-555-0302',
                'website' => 'https://expresslogistics.com',
                'tax_id' => 'TAX-34567890',
                'payment_terms' => 'COD',
                'credit_limit' => 25000.00,
                'currency' => 'USD',
                'rating' => 3.5,
                'status' => 'active',
                'notes' => 'Shipping and logistics provider',
            ],
            [
                'tenant_id' => 1,
                'code' => 'SUP-00005',
                'name' => 'European Electronics GmbH',
                'email' => 'sales@euroelectronics.de',
                'phone' => '+49-30-12345678',
                'mobile' => '+49-151-12345678',
                'website' => 'https://euroelectronics.de',
                'tax_id' => 'DE123456789',
                'payment_terms' => 'Net 90',
                'credit_limit' => 200000.00,
                'currency' => 'EUR',
                'rating' => 4.2,
                'status' => 'active',
                'notes' => 'Electronic components and devices',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }

        $this->command->info('✓ Created '.count($suppliers).' sample suppliers');
    }
}
