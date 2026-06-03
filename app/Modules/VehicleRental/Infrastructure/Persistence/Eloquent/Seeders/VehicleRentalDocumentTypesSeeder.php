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
        DB::table('document_types')->upsert(
            VehicleRentalDocumentSeedCatalog::documentTypes(),
            ['code'],
            ['name', 'default_status', 'is_active', 'requires_source'],
        );
    }
}
