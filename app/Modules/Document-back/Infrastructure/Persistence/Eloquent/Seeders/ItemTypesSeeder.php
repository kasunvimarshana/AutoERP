<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class ItemTypesSeeder extends Seeder
{
    public function run(): void
    {
        $records = array_map(
            static fn (array $type): array => $type + ['created_at' => now(), 'updated_at' => now()],
            DocumentSeedCatalog::itemTypes()
        );

        DB::table('document_item_types')->upsert(
            $records,
            ['code'],
            ['name', 'display_name', 'is_active', 'updated_at']
        );
    }
}
