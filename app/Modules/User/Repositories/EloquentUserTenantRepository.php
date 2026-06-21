<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserTenantModel;

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

    public function existsForTenantAndUser(int $tenantId, int $userId): bool
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function existsForTenantUserAndOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): bool
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('organization_unit_id', $organizationUnitId)
            ->exists();
    }

    /** @return list<DataRecord> */
    public function listDefaultsForTenantAndUser(int $tenantId, int $userId): array
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->orderBy('id')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->toRecord($model))
            ->values()
            ->all();
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
