<?php

namespace Modules\Workflow\Infrastructure\Repositories;

use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Infrastructure\Models\WorkflowModel;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return WorkflowModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return WorkflowModel::where('tenant_id', $tenantId)->get();
    }

    public function findActiveByDocumentType(string $tenantId, string $documentType): ?object
    {
        return WorkflowModel::where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): object
    {
        return WorkflowModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = WorkflowModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        WorkflowModel::findOrFail($id)->delete();
    }
}
