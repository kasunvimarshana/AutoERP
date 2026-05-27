<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $records = array_map(
            static fn (array $type): array => $type + ['created_at' => now(), 'updated_at' => now()],
            DocumentSeedCatalog::documentTypes()
        );

        DB::table('document_types')->upsert(
            $records,
            ['code'],
            ['name', 'default_status', 'is_active', 'requires_source', 'updated_at']
        );
    }
}
