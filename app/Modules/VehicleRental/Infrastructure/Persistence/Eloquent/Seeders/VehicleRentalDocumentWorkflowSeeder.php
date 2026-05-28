<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleRentalDocumentSeedCatalog;

class VehicleRentalDocumentWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VehicleRentalDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');

        foreach (VehicleRentalDocumentSeedCatalog::workflowBlueprints() as $typeCode => $workflow) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;
            if ($documentTypeId === null) {
                continue;
            }

            DB::table('document_workflows')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'document_type_id' => $documentTypeId,
                    'name' => $workflow['name'],
                ],
                [
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $workflowId = (int) DB::table('document_workflows')
                ->where('tenant_id', $tenantId)
                ->where('document_type_id', $documentTypeId)
                ->where('name', $workflow['name'])
                ->value('id');
            if ($workflowId <= 0) {
                continue;
            }

            $stepRows = [];
            foreach ((array) ($workflow['steps'] ?? []) as $step) {
                if (! is_array($step)) {
                    continue;
                }

                $stepRows[] = [
                    'workflow_id' => $workflowId,
                    'sequence' => (int) ($step['sequence'] ?? 1),
                    'name' => (string) ($step['name'] ?? 'draft'),
                    'display_name' => (string) ($step['display_name'] ?? ucfirst((string) ($step['name'] ?? 'draft'))),
                    'is_initial' => (bool) ($step['is_initial'] ?? false),
                    'is_terminal' => (bool) ($step['is_terminal'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($stepRows !== []) {
                DB::table('document_workflow_steps')->upsert(
                    $stepRows,
                    ['workflow_id', 'name'],
                    ['sequence', 'display_name', 'is_initial', 'is_terminal', 'updated_at'],
                );
            }

            $stepIdByName = DB::table('document_workflow_steps')
                ->where('workflow_id', $workflowId)
                ->pluck('id', 'name');

            $transitionRows = [];
            foreach ((array) ($workflow['transitions'] ?? []) as $transition) {
                if (! is_array($transition)) {
                    continue;
                }

                $fromStepId = $stepIdByName[$transition['from']] ?? null;
                $toStepId = $stepIdByName[$transition['to']] ?? null;
                if ($fromStepId === null || $toStepId === null) {
                    continue;
                }

                $transitionRows[] = [
                    'from_step_id' => (int) $fromStepId,
                    'to_step_id' => (int) $toStepId,
                    'action_name' => (string) ($transition['action_name'] ?? 'transition'),
                    'condition_expression' => $transition['condition_expression'] ?? null,
                    'required_ability' => $transition['required_ability'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($transitionRows !== []) {
                DB::table('document_workflow_transitions')->upsert(
                    $transitionRows,
                    ['from_step_id', 'to_step_id', 'action_name'],
                    ['condition_expression', 'required_ability', 'updated_at'],
                );
            }
        }
    }
}
