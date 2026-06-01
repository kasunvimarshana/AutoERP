<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseDocumentDefinitionsSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseDocumentItemTypesSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseDocumentSequenceSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseDocumentTypesSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseDocumentWorkflowSeeder;

class PurchaseModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PurchaseDocumentTypesSeeder::class,
            PurchaseDocumentItemTypesSeeder::class,
            PurchaseDocumentDefinitionsSeeder::class,
            PurchaseDocumentWorkflowSeeder::class,
            PurchaseDocumentSequenceSeeder::class,
            PurchaseSettingsSeeder::class,
        ]);
    }
}
