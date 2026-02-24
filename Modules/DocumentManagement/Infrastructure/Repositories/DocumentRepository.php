<?php

namespace Modules\DocumentManagement\Infrastructure\Repositories;

use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Infrastructure\Models\DocumentModel;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return DocumentModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return DocumentModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return DocumentModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = DocumentModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        DocumentModel::findOrFail($id)->delete();
    }
}
