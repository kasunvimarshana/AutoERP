<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class WorkflowTransitionsSeeder extends Seeder
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

            foreach ($workflowBlueprint['transitions'] as $transition) {
                $fromStepId = DB::table('document_workflow_steps')
                    ->where('workflow_id', $workflowId)
                    ->where('name', $transition['from'])
                    ->value('id');
                $toStepId = DB::table('document_workflow_steps')
                    ->where('workflow_id', $workflowId)
                    ->where('name', $transition['to'])
                    ->value('id');

                if ($fromStepId === null || $toStepId === null) {
                    continue;
                }

                $records[] = [
                    'from_step_id' => $fromStepId,
                    'to_step_id' => $toStepId,
                    'action_name' => $transition['action_name'],
                    'condition_expression' => $transition['condition_expression'] ?? null,
                    'required_ability' => $transition['required_ability'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('document_workflow_transitions')->upsert(
            $records,
            ['from_step_id', 'to_step_id', 'action_name'],
            ['condition_expression', 'required_ability', 'updated_at']
        );
    }
}
