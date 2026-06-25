<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Models\UserOrganizationUnitModel;

final class EloquentUserOrganizationUnitRepository extends EloquentRepository implements UserOrganizationUnitRepositoryInterface
{
    public function __construct(UserOrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function findAssignment(
        int $tenantId,
        int $organizationUnitId,
        int $userId,
        ?int $excludeId = null,
    ): ?DataRecord {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('user_id', $userId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function existsForTenantUserAndOrganizationUnit(
        int $tenantId,
        int $userId,
        int $organizationUnitId,
    ): bool {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->exists();
    }

    /** @return list<DataRecord> */
    public function listDefaultsForTenantAndUser(int $tenantId, int $userId): array
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('default_marker', 'default')
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->toRecord($model))
            ->values()
            ->all();
    }


    public function findDefaultForTenantAndUser(int $tenantId, int $userId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('default_marker', 'default')
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function firstActiveForTenantAndUser(int $tenantId, int $userId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->orderBy('organization_unit_id')
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('default_marker', 'default');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update([
            'is_default' => false,
            'default_marker' => null,
        ]);
    }


    public function setDefault(int $tenantId, int $userId, int $organizationUnitId): bool
    {
        $this->clearDefaultForUser($tenantId, $userId);

        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->update([
                'is_default' => true,
                'default_marker' => 'default',
                'updated_at' => now(),
            ]) === 1;
    }

    public function deleteAssignment(int|string $id, int $tenantId, int $userId): bool
    {
        return $this->query()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->delete() === 1;
    }

}
