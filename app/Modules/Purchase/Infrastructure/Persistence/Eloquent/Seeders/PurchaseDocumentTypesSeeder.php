<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support\PurchaseDocumentSeedCatalog;

class PurchaseDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PurchaseDocumentSeedCatalog::documentTypes() as $documentType) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $documentType['code']],
                [
                    'default_status' => $documentType['default_status'],
                    'is_active' => $documentType['is_active'],
                    'name' => $documentType['name'],
                    'requires_source' => $documentType['requires_source'],
                    'updated_at' => now(),
                ],
            );
        }
    }
}
