<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\Support\VoucherDocumentSeedCatalog;

class VoucherDocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VoucherDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $periodValue = date('Y');

        foreach (VoucherDocumentSeedCatalog::sequences() as $sequence) {
            DB::table('sequences')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'document_type' => $sequence['document_type'],
                    'period_value' => $periodValue,
                ],
                [
                    'prefix' => $sequence['prefix'],
                    'suffix' => '',
                    'padding' => 5,
                    'next_number' => 1,
                    'period_type' => 'yearly',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
