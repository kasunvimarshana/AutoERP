<?php

namespace Modules\DocumentManagement\Infrastructure\Repositories;

use Modules\DocumentManagement\Domain\Contracts\DocumentCategoryRepositoryInterface;
use Modules\DocumentManagement\Infrastructure\Models\DocumentCategoryModel;

class DocumentCategoryRepository implements DocumentCategoryRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return DocumentCategoryModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return DocumentCategoryModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return DocumentCategoryModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = DocumentCategoryModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        DocumentCategoryModel::findOrFail($id)->delete();
    }
}
