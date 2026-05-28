<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleServiceDocumentSeedCatalog;

class VehicleServiceDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (VehicleServiceDocumentSeedCatalog::documentTypes() as $code => $data) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
