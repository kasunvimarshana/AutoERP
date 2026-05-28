<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support\PurchaseDocumentSeedCatalog;

class PurchaseDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('document_types')->upsert(
            PurchaseDocumentSeedCatalog::documentTypes(),
            ['code'],
            ['name', 'default_status', 'is_active', 'requires_source'],
        );
    }
}
