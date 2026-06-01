<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\Support\SalesDocumentSeedCatalog;

class SalesDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SalesDocumentSeedCatalog::documentTypes() as $documentType) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $documentType['code']],
                $documentType,
            );
        }
    }
}
