<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalUomsSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['code' => 'EA', 'name' => 'Each', 'category_code' => 'count', 'precision_scale' => 0],
            ['code' => 'BOX', 'name' => 'Box', 'category_code' => 'count', 'precision_scale' => 2],
            ['code' => 'PCS', 'name' => 'Pieces', 'category_code' => 'count', 'precision_scale' => 0],
            ['code' => 'KG', 'name' => 'Kilogram', 'category_code' => 'weight', 'precision_scale' => 3],
            ['code' => 'G', 'name' => 'Gram', 'category_code' => 'weight', 'precision_scale' => 3],
            ['code' => 'L', 'name' => 'Litre', 'category_code' => 'volume', 'precision_scale' => 3],
            ['code' => 'ML', 'name' => 'Millilitre', 'category_code' => 'volume', 'precision_scale' => 3],
            ['code' => 'M', 'name' => 'Metre', 'category_code' => 'length', 'precision_scale' => 3],
            ['code' => 'CM', 'name' => 'Centimetre', 'category_code' => 'length', 'precision_scale' => 3],
            ['code' => 'HR', 'name' => 'Hour', 'category_code' => 'time', 'precision_scale' => 2],
            ['code' => 'DAY', 'name' => 'Day', 'category_code' => 'time', 'precision_scale' => 2],
        ];

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($uoms as $uom) {
                DB::table('uoms')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'code' => $uom['code'],
                    ],
                    array_merge($uom, [
                        'tenant_id' => $tenantId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
