<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DefaultUnitsSeeder
 *
 * Seeds default measurement units for all tenants
 */
class DefaultUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = DB::table('tenants')->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run TenantSeeder first.');

            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedUnitsForTenant($tenant->id);
        }

        $this->command->info('Default units seeded successfully for all tenants!');
    }

    /**
     * Seed units for a specific tenant
     */
    private function seedUnitsForTenant(string $tenantId): void
    {
        $units = [
            // Quantity/Count
            ['name' => 'Piece', 'symbol' => 'pcs', 'type' => 'count'],
            ['name' => 'Dozen', 'symbol' => 'doz', 'type' => 'count'],
            ['name' => 'Gross', 'symbol' => 'gr', 'type' => 'count'],
            ['name' => 'Box', 'symbol' => 'box', 'type' => 'count'],
            ['name' => 'Carton', 'symbol' => 'ctn', 'type' => 'count'],
            ['name' => 'Pallet', 'symbol' => 'plt', 'type' => 'count'],

            // Weight
            ['name' => 'Milligram', 'symbol' => 'mg', 'type' => 'weight'],
            ['name' => 'Gram', 'symbol' => 'g', 'type' => 'weight'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight'],
            ['name' => 'Ton', 'symbol' => 't', 'type' => 'weight'],
            ['name' => 'Ounce', 'symbol' => 'oz', 'type' => 'weight'],
            ['name' => 'Pound', 'symbol' => 'lb', 'type' => 'weight'],

            // Volume
            ['name' => 'Milliliter', 'symbol' => 'ml', 'type' => 'volume'],
            ['name' => 'Liter', 'symbol' => 'l', 'type' => 'volume'],
            ['name' => 'Gallon', 'symbol' => 'gal', 'type' => 'volume'],
            ['name' => 'Cubic Meter', 'symbol' => 'm³', 'type' => 'volume'],

            // Length
            ['name' => 'Millimeter', 'symbol' => 'mm', 'type' => 'length'],
            ['name' => 'Centimeter', 'symbol' => 'cm', 'type' => 'length'],
            ['name' => 'Meter', 'symbol' => 'm', 'type' => 'length'],
            ['name' => 'Kilometer', 'symbol' => 'km', 'type' => 'length'],
            ['name' => 'Inch', 'symbol' => 'in', 'type' => 'length'],
            ['name' => 'Foot', 'symbol' => 'ft', 'type' => 'length'],
            ['name' => 'Yard', 'symbol' => 'yd', 'type' => 'length'],

            // Area
            ['name' => 'Square Meter', 'symbol' => 'm²', 'type' => 'area'],
            ['name' => 'Square Foot', 'symbol' => 'ft²', 'type' => 'area'],
            ['name' => 'Acre', 'symbol' => 'ac', 'type' => 'area'],

            // Time (for services)
            ['name' => 'Hour', 'symbol' => 'hr', 'type' => 'time'],
            ['name' => 'Day', 'symbol' => 'day', 'type' => 'time'],
            ['name' => 'Week', 'symbol' => 'wk', 'type' => 'time'],
            ['name' => 'Month', 'symbol' => 'mo', 'type' => 'time'],
        ];

        $now = now();
        foreach ($units as $unit) {
            // Check if unit already exists
            $exists = DB::table('units')
                ->where('tenant_id', $tenantId)
                ->where('symbol', $unit['symbol'])
                ->exists();

            if (! $exists) {
                DB::table('units')->insert([
                    'id' => Str::uuid()->toString(),
                    'tenant_id' => $tenantId,
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'type' => $unit['type'],
                    'metadata' => json_encode([]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
