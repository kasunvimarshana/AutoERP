<?php

namespace App\Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalInventoryAdjustmentReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['reason_code' => 'COUNT_GAIN', 'reason_name' => 'Stock Count Gain', 'category_code' => 'count'],
            ['reason_code' => 'COUNT_LOSS', 'reason_name' => 'Stock Count Loss', 'category_code' => 'count'],
            ['reason_code' => 'DAMAGE', 'reason_name' => 'Damaged Inventory', 'category_code' => 'damage'],
            ['reason_code' => 'SCRAP', 'reason_name' => 'Scrapped Inventory', 'category_code' => 'scrap'],
            ['reason_code' => 'EXPIRED', 'reason_name' => 'Expired Inventory', 'category_code' => 'expiry'],
            ['reason_code' => 'OPENING_BAL', 'reason_name' => 'Opening Balance', 'category_code' => 'opening'],
            ['reason_code' => 'MANUAL_CORR', 'reason_name' => 'Manual Correction', 'category_code' => 'correction'],
            ['reason_code' => 'TRANSFER_VAR', 'reason_name' => 'Transfer Variance', 'category_code' => 'transfer'],
        ];

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($reasons as $reason) {
                DB::table('inventory_adjustment_reasons')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'reason_code' => $reason['reason_code'],
                    ],
                    array_merge($reason, [
                        'tenant_id' => $tenantId,
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
