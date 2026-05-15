<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Document\Domain\Entities\DocumentWorkflow;
use Modules\Document\Domain\Repositories\DocumentWorkflowRepositoryInterface;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentWorkflowModel;

class EloquentDocumentWorkflowRepository implements DocumentWorkflowRepositoryInterface
{
    public function findActive(int $tenantId, int $documentTypeId): ?DocumentWorkflow
    {
        $model = DocumentWorkflowModel::query()
            ->with(['steps.outgoingTransitions'])
            ->where('tenant_id', $tenantId)
            ->where('document_type_id', $documentTypeId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if ($model === null) {
            return null;
        }

        $steps = [];
        $transitions = [];

        foreach ($model->steps as $step) {
            $steps[] = [
                'id' => $step->id,
                'name' => $step->name,
                'display_name' => $step->display_name,
                'sequence' => $step->sequence,
            ];

            foreach ($step->outgoingTransitions as $transition) {
                $transitions[] = [
                    'from' => $step->name,
                    'to' => optional($model->steps->firstWhere('id', $transition->to_step_id))->name,
                    'action_name' => $transition->action_name,
                    'condition_expression' => $transition->condition_expression,
                    'required_ability' => $transition->required_ability,
                ];
            }
        }

        return new DocumentWorkflow(
            id: $model->id,
            tenantId: $model->tenant_id,
            documentTypeId: $model->document_type_id,
            name: $model->name,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            steps: $steps,
            transitions: $transitions,
        );
    }
}
