<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingModel;

final class EloquentOrganizationUnitSettingRepository extends EloquentRepository implements OrganizationUnitSettingRepositoryInterface
{
    public function __construct(OrganizationUnitSettingModel $model)
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

    public function findByTenantAndOrganizationUnitAndGroupAndKey(
        int|string $tenantId,
        int|string $organizationUnitId,
        int|string $groupId,
        string $key,
    ): ?DataRecord {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('group_id', $groupId)
            ->where('key', trim($key))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}