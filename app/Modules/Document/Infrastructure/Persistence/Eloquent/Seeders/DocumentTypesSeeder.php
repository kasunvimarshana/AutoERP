<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', DocumentSeedCatalog::defaultTenantCode())
            ->value('id');

        $records = array_map(
            static fn (array $type): array => $type + [
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            DocumentSeedCatalog::documentTypes()
        );

        DB::table('document_types')->upsert(
            $records,
            ['tenant_id', 'code'],
            [
                'name',
                'default_status',
                'is_active',
                'requires_source',
                'supports_items',
                'supports_attachments',
                'supports_comments',
                'supports_versions',
                'supports_workflow',
                'updated_at',
            ]
        );
    }
}
