<?php

namespace Modules\Helpdesk\Infrastructure\Repositories;

use Modules\Helpdesk\Domain\Contracts\TicketCategoryRepositoryInterface;
use Modules\Helpdesk\Infrastructure\Models\TicketCategoryModel;

class TicketCategoryRepository implements TicketCategoryRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return TicketCategoryModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return TicketCategoryModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return TicketCategoryModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = TicketCategoryModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        TicketCategoryModel::findOrFail($id)->delete();
    }
}
