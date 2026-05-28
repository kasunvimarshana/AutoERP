<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\Support\SalesDocumentSeedCatalog;

class SalesDocumentItemTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('document_item_types')->upsert(
            SalesDocumentSeedCatalog::itemTypes(),
            ['code'],
            ['name', 'display_name', 'is_active'],
        );
    }
}
