<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\Tenant\Models\TenantSettingGroupModel;
use Modules\Tenant\Repositories\TenantSettingGroupRepositoryInterface as GroupRepositoryInterface;

final class EloquentTenantSettingGroupRepository extends EloquentRepository implements GroupRepositoryInterface
{
    public function __construct(TenantSettingGroupModel $model)
    {
        parent::__construct($model);
    }

    public function listByTenant(int|string $tenantId): array
    {
        $records = [];

        foreach ($this->query()->where('tenant_id', $tenantId)->orderBy('key')->get() as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findByTenantAndKey(int|string $tenantId, string $key): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('key', trim($key))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}
