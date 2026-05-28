<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ItemTypesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $now = now();

        $types = [
            ['code' => 'PRODUCT', 'name' => 'Product', 'is_stockable' => true],
            ['code' => 'SERVICE', 'name' => 'Service', 'is_service' => true],
            ['code' => 'LABOUR', 'name' => 'Labour Item', 'is_service' => true],
            ['code' => 'COMBO', 'name' => 'Combo/Bundle', 'is_chargeable' => true],
            ['code' => 'NON_INVENTORY', 'name' => 'Non-Inventory Item', 'is_chargeable' => true],
            ['code' => 'VARIABLE', 'name' => 'Variable/Custom Item', 'is_chargeable' => true],
            ['code' => 'FEE', 'name' => 'Fee/Charge Item', 'is_chargeable' => true],
            ['code' => 'RENTAL_CHARGE', 'name' => 'Rental Charge Item', 'is_rentable' => true, 'is_chargeable' => true],
            ['code' => 'DISCOUNT', 'name' => 'Discount/Adjustment Item', 'is_chargeable' => true],
        ];

        foreach ($types as $type) {
            DB::table('item_types')->updateOrInsert(
                ['tenant_id' => null, 'code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => null,
                    'is_stockable' => (bool) ($type['is_stockable'] ?? false),
                    'is_service' => (bool) ($type['is_service'] ?? false),
                    'is_rentable' => (bool) ($type['is_rentable'] ?? false),
                    'is_chargeable' => (bool) ($type['is_chargeable'] ?? false),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
