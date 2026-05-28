<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support\PurchaseDocumentSeedCatalog;

class PurchaseDocumentItemTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('document_item_types')->upsert(
            PurchaseDocumentSeedCatalog::itemTypes(),
            ['code'],
            ['name', 'display_name', 'is_active'],
        );
    }
}
