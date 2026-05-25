<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserTenantModel;

final class EloquentUserTenantRepository extends EloquentRepository implements UserTenantRepositoryInterface
{
    public function __construct(UserTenantModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantOrganizationUser(int $tenantId, ?int $organizationUnitId, int $userId, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId);

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_default', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => false]);
    }
}
