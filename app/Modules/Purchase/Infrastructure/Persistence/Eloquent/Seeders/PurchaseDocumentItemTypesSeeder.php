<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support\PurchaseDocumentSeedCatalog;

class PurchaseDocumentItemTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PurchaseDocumentSeedCatalog::itemTypes() as $itemType) {
            DB::table('document_item_types')->updateOrInsert(
                ['code' => $itemType['code']],
                [
                    'display_name' => $itemType['display_name'],
                    'is_active' => $itemType['is_active'],
                    'name' => $itemType['name'],
                    'updated_at' => now(),
                ],
            );
        }
    }
}
