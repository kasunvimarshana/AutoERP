<?php

namespace Modules\Helpdesk\Infrastructure\Repositories;

use Modules\Helpdesk\Domain\Contracts\KbCategoryRepositoryInterface;
use Modules\Helpdesk\Infrastructure\Models\KbCategoryModel;

class KbCategoryRepository implements KbCategoryRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return KbCategoryModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return KbCategoryModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return KbCategoryModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = KbCategoryModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        KbCategoryModel::findOrFail($id)->delete();
    }
}
