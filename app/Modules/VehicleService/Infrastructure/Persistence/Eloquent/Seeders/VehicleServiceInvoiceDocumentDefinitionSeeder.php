<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class VehicleServiceInvoiceDocumentDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([VehicleServiceDocumentDefinitionsSeeder::class]);
    }
}
