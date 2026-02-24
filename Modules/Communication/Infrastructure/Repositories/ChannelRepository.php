<?php

namespace Modules\Communication\Infrastructure\Repositories;

use Modules\Communication\Domain\Contracts\ChannelRepositoryInterface;
use Modules\Communication\Infrastructure\Models\ChannelModel;

class ChannelRepository implements ChannelRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ChannelModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ChannelModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ChannelModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ChannelModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ChannelModel::findOrFail($id)->delete();
    }
}
