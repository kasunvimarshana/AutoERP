<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleRentalDocumentSeedCatalog;

class VehicleRentalDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (VehicleRentalDocumentSeedCatalog::documentTypes() as $documentType) {
            DB::table('document_types')->updateOrInsert(
                [
                    'tenant_id' => $documentType['tenant_id'] ?? null,
                    'code' => $documentType['code'],
                ],
                $documentType,
            );
        }
    }
}
