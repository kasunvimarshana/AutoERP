<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentWorkflowsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', DocumentSeedCatalog::DEFAULT_TENANT_CODE)->value('id');
        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');

        $records = [];

        foreach (DocumentSeedCatalog::workflowBlueprints() as $typeCode => $workflow) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;

            if ($documentTypeId === null) {
                continue;
            }

            $records[] = [
                'tenant_id' => $tenantId,
                'document_type_id' => $documentTypeId,
                'name' => $workflow['name'],
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('document_workflows')->upsert(
            $records,
            ['tenant_id', 'document_type_id', 'name'],
            ['is_default', 'is_active', 'updated_at']
        );
    }
}
