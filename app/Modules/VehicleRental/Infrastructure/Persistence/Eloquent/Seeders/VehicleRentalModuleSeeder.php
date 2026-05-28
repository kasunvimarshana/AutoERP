<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class VehicleRentalModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalDocumentTypesSeeder::class,
            \Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalDocumentDefinitionsSeeder::class,
            \Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalDocumentWorkflowSeeder::class,
            \Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalDocumentSequenceSeeder::class,
            \Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalSettingsSeeder::class,
        ]);
    }
}
