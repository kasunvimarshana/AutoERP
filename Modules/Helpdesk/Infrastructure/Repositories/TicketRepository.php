<?php

namespace Modules\Helpdesk\Infrastructure\Repositories;

use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Infrastructure\Models\TicketModel;

class TicketRepository implements TicketRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return TicketModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return TicketModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return TicketModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = TicketModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        TicketModel::findOrFail($id)->delete();
    }
}
