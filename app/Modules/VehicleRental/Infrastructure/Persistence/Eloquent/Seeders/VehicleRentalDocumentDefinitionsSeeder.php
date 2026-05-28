<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleRentalDocumentSeedCatalog;

class VehicleRentalDocumentDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VehicleRentalDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');

        foreach (VehicleRentalDocumentSeedCatalog::documentDefinitionNames() as $typeCode => $name) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;
            if ($documentTypeId === null) {
                continue;
            }

            DB::table('document_definitions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'document_type_id' => $documentTypeId,
                    'version' => 1,
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
