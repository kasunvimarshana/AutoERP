<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', DocumentSeedCatalog::DEFAULT_TENANT_CODE)->value('id');
        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');

        $records = [];

        foreach (DocumentSeedCatalog::documentDefinitions() as $typeCode => $definition) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;

            if ($documentTypeId === null) {
                continue;
            }

            $records[] = [
                'tenant_id' => $tenantId,
                'document_type_id' => $documentTypeId,
                'version' => 1,
                'name' => $definition['name'],
                'header_schema' => json_encode($definition['header_schema'], JSON_THROW_ON_ERROR),
                'allowed_item_types' => json_encode($definition['allowed_item_types'], JSON_THROW_ON_ERROR),
                'validation_rules' => json_encode($definition['validation_rules'], JSON_THROW_ON_ERROR),
                'form_layout' => json_encode($definition['form_layout'], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('document_definitions')->upsert(
            $records,
            ['tenant_id', 'document_type_id', 'version'],
            ['name', 'header_schema', 'allowed_item_types', 'validation_rules', 'form_layout', 'is_active', 'updated_at']
        );
    }
}
