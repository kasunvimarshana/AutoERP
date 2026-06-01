<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class SalesModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SalesDocumentTypesSeeder::class,
            SalesDocumentItemTypesSeeder::class,
            SalesDocumentDefinitionsSeeder::class,
            SalesDocumentWorkflowSeeder::class,
            SalesDocumentSequenceSeeder::class,
            SalesSettingsSeeder::class,
            SalesSampleSeeder::class,
        ]);
    }
}
