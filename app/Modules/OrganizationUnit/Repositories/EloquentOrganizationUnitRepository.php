<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->query()->where('tenant_id', $tenantId)->count();
    }

    public function listByTenant(int|string $tenantId): array
    {
        $records = [];

        foreach ($this->query()->where('tenant_id', $tenantId)->orderBy('name')->get() as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('name', trim($name))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByTenantAndCode(int|string $tenantId, string $code): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('code', trim($code))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByTenantAndPath(int|string $tenantId, string $path): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('path', trim($path))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}
