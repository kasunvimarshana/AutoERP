<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\UserDevice;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;

class EloquentUserDeviceRepository extends EloquentRepository implements UserDeviceRepositoryInterface
{
    public function __construct(UserDeviceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UserDeviceModel $model): UserDevice => $this->mapModelToDomainEntity($model));
    }

    public function findByUserAndToken(int $userId, string $deviceToken): ?UserDevice
    {
        $tenantId = $this->resolveTenantId();

        $query = $this->model
            ->where('user_id', $userId)
            ->where('device_token', $deviceToken);

        if ($tenantId !== null) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('tenant_id', $tenantId));
        }

        $model = $query->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function paginateByUser(int $userId, ?string $platform, int $perPage, int $page): LengthAwarePaginator
    {
        $tenantId = $this->resolveTenantId();

        $query = $this->model->newQuery()->where('user_id', $userId);

        if ($platform !== null && $platform !== '') {
            $query->where('platform', $platform);
        }

        if ($tenantId !== null) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('tenant_id', $tenantId));
        }

        return $query
            ->orderByDesc('last_active_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (UserDeviceModel $model): UserDevice => $this->mapModelToDomainEntity($model));
    }

    public function save(UserDevice $device): UserDevice
    {
        $data = [
            'user_id' => $device->getUserId(),
            'device_token' => $device->getDeviceToken(),
            'platform' => $device->getPlatform(),
            'device_name' => $device->getDeviceName(),
            'last_active_at' => $device->getLastActiveAt(),
        ];

        if ($device->getId()) {
            $model = $this->update($device->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UserDeviceModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    public function find($id, array $columns = ['*']): ?UserDevice
    {
        $tenantId = $this->resolveTenantId();

        $query = $this->model->newQuery()->where('id', (int) $id);
        if ($tenantId !== null) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('tenant_id', $tenantId));
        }

        $model = $query->first($columns);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function delete($id): bool
    {
        $tenantId = $this->resolveTenantId();

        $query = $this->model->newQuery()->where('id', (int) $id);
        if ($tenantId !== null) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('tenant_id', $tenantId));
        }

        $model = $query->first();
        if (! $model) {
            return false;
        }

        return (bool) $model->delete();
    }

    private function mapModelToDomainEntity(UserDeviceModel $model): UserDevice
    {
        return new UserDevice(
            userId: (int) $model->user_id,
            deviceToken: (string) $model->device_token,
            platform: $model->platform,
            deviceName: $model->device_name,
            lastActiveAt: $model->last_active_at,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    private function resolveTenantId(): ?int
    {
        $authTenantId = Auth::user()?->tenant_id;
        if ($authTenantId !== null) {
            return (int) $authTenantId;
        }

        $headerTenantId = Request::header('X-Tenant-ID');

        return is_numeric($headerTenantId) ? (int) $headerTenantId : null;
    }
}
