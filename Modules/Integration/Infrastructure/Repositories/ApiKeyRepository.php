<?php

namespace Modules\Integration\Infrastructure\Repositories;

use Modules\Integration\Domain\Contracts\ApiKeyRepositoryInterface;
use Modules\Integration\Infrastructure\Models\ApiKeyModel;

class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ApiKeyModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ApiKeyModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ApiKeyModel::create($data);
    }

    public function revoke(string $id): object
    {
        $model = ApiKeyModel::findOrFail($id);
        $model->update(['is_active' => false]);

        return $model->fresh();
    }
}
