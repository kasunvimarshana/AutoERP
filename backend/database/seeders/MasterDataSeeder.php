<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\MasterData\Models\Currency;
use App\Modules\MasterData\Models\Country;
use App\Modules\MasterData\Models\UnitOfMeasure;
use App\Modules\MasterData\Models\TaxRate;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-company')->first();
        
        if (!$tenant) {
            $this->command->error('Tenant not found. Please run TenantSeeder first.');
            return;
        }

        // Seed Currencies
        $this->seedCurrencies($tenant);
        
        // Seed Countries
        $this->seedCountries();
        
        // Seed Units of Measure
        $this->seedUnitsOfMeasure($tenant);
        
        // Seed Tax Rates
        $this->seedTaxRates($tenant);
        
        $this->command->info('Master data seeded successfully!');
    }

    private function seedCurrencies(Tenant $tenant): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0000,
                'is_base_currency' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.85,
                'is_base_currency' => false,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'exchange_rate' => 0.73,
                'is_base_currency' => false,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'exchange_rate' => 110.50,
                'is_base_currency' => false,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'code' => 'INR',
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'exchange_rate' => 74.50,
                'is_base_currency' => false,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }

        $this->command->info('Currencies seeded.');
    }

    private function seedCountries(): void
    {
        $countries = [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'CN', 'name' => 'China'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }

        $this->command->info('Countries seeded.');
    }

    private function seedUnitsOfMeasure(Tenant $tenant): void
    {
        $units = [
            // Quantity
            [
                'name' => 'Piece',
                'abbreviation' => 'pc',
                'type' => 'quantity',
                'base_unit_id' => null,
                'conversion_factor' => 1.0,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Dozen',
                'abbreviation' => 'doz',
                'type' => 'quantity',
                'base_unit_id' => null, // Will be set after creating piece
                'conversion_factor' => 12.0,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Box',
                'abbreviation' => 'box',
                'type' => 'quantity',
                'base_unit_id' => null, // Will be set after creating piece
                'conversion_factor' => 1.0,
                'is_system' => false,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            // Weight
            [
                'name' => 'Kilogram',
                'abbreviation' => 'kg',
                'type' => 'weight',
                'base_unit_id' => null,
                'conversion_factor' => 1.0,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Gram',
                'abbreviation' => 'g',
                'type' => 'weight',
                'base_unit_id' => null, // Will be set after creating kg
                'conversion_factor' => 0.001,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Pound',
                'abbreviation' => 'lb',
                'type' => 'weight',
                'base_unit_id' => null, // Will be set after creating kg
                'conversion_factor' => 0.453592,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            // Length
            [
                'name' => 'Meter',
                'abbreviation' => 'm',
                'type' => 'length',
                'base_unit_id' => null,
                'conversion_factor' => 1.0,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Centimeter',
                'abbreviation' => 'cm',
                'type' => 'length',
                'base_unit_id' => null, // Will be set after creating m
                'conversion_factor' => 0.01,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            // Volume
            [
                'name' => 'Liter',
                'abbreviation' => 'L',
                'type' => 'volume',
                'base_unit_id' => null,
                'conversion_factor' => 1.0,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Milliliter',
                'abbreviation' => 'mL',
                'type' => 'volume',
                'base_unit_id' => null, // Will be set after creating L
                'conversion_factor' => 0.001,
                'is_system' => true,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
        ];

        // Create units and set relationships
        $createdUnits = [];
        foreach ($units as $unitData) {
            $unit = UnitOfMeasure::create($unitData);
            $createdUnits[$unit->abbreviation] = $unit;
        }

        // Update base_unit_id for derived units
        $createdUnits['doz']->update(['base_unit_id' => $createdUnits['pc']->id]);
        $createdUnits['box']->update(['base_unit_id' => $createdUnits['pc']->id]);
        $createdUnits['g']->update(['base_unit_id' => $createdUnits['kg']->id]);
        $createdUnits['lb']->update(['base_unit_id' => $createdUnits['kg']->id]);
        $createdUnits['cm']->update(['base_unit_id' => $createdUnits['m']->id]);
        $createdUnits['mL']->update(['base_unit_id' => $createdUnits['L']->id]);

        $this->command->info('Units of measure seeded.');
    }

    private function seedTaxRates(Tenant $tenant): void
    {
        $taxRates = [
            [
                'name' => 'Standard VAT',
                'code' => 'VAT-STD',
                'rate' => 20.00,
                'type' => 'vat',
                'is_active' => true,
                'is_compound' => false,
                'effective_from' => now()->subYear(),
                'effective_to' => null,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Reduced VAT',
                'code' => 'VAT-RED',
                'rate' => 5.00,
                'type' => 'vat',
                'is_active' => true,
                'is_compound' => false,
                'effective_from' => now()->subYear(),
                'effective_to' => null,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Zero VAT',
                'code' => 'VAT-ZERO',
                'rate' => 0.00,
                'type' => 'vat',
                'is_active' => true,
                'is_compound' => false,
                'effective_from' => now()->subYear(),
                'effective_to' => null,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Sales Tax',
                'code' => 'SALES-TAX',
                'rate' => 8.00,
                'type' => 'sales_tax',
                'is_active' => true,
                'is_compound' => false,
                'effective_from' => now()->subYear(),
                'effective_to' => null,
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($taxRates as $taxRate) {
            TaxRate::create($taxRate);
        }

        $this->command->info('Tax rates seeded.');
    }
}
