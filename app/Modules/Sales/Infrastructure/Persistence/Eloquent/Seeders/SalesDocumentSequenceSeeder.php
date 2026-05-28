<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\Support\SalesDocumentSeedCatalog;

class SalesDocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', SalesDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $periodValue = date('Y');

        foreach (SalesDocumentSeedCatalog::sequences() as $sequence) {
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
                    'padding' => $sequence['padding'],
                    'next_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
