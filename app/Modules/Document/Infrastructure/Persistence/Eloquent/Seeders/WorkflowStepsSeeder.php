<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class WorkflowStepsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', DocumentSeedCatalog::defaultTenantCode())->value('id');
        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');
        $workflowIds = DB::table('document_workflows')
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy(fn (object $workflow): string => "{$workflow->document_type_id}:{$workflow->name}");

        $records = [];

        foreach (DocumentSeedCatalog::workflowBlueprints() as $typeCode => $workflowBlueprint) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;

            if ($documentTypeId === null) {
                continue;
            }

            $workflowId = optional($workflowIds->get("{$documentTypeId}:{$workflowBlueprint['name']}"))->id;

            if ($workflowId === null) {
                continue;
            }

            foreach ($workflowBlueprint['steps'] as $step) {
                $records[] = $step + [
                    'workflow_id' => $workflowId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('document_workflow_steps')->upsert(
            $records,
            ['workflow_id', 'name'],
            ['sequence', 'display_name', 'is_initial', 'is_terminal', 'updated_at']
        );
    }
}
