<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\User\Application\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;

final class EloquentUserDeviceRepository extends EloquentRepository implements UserDeviceRepositoryInterface
{
    public function __construct(UserDeviceModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserDeviceToken(?int $tenantId, int $userId, string $deviceToken, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('device_token', trim($deviceToken));

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}
