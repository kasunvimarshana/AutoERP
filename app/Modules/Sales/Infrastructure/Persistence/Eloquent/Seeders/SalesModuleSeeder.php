<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesDocumentDefinitionsSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesDocumentItemTypesSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesDocumentSequenceSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesDocumentTypesSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesDocumentWorkflowSeeder;

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
        ]);
    }
}
