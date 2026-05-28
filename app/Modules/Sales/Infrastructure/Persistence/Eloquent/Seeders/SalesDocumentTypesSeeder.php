<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\Support\SalesDocumentSeedCatalog;

class SalesDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('document_types')->upsert(
            SalesDocumentSeedCatalog::documentTypes(),
            ['code'],
            ['name', 'default_status', 'is_active', 'requires_source'],
        );
    }
}
