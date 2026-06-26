<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserDevicePlatform;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserDeviceModel;
use Modules\User\Models\UserModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class UserDeviceService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly UserAuditService $audit,
        private readonly ClockInterface $clock,
    ) {}

    public function list(int|string $userId, array $filters): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DEVICES_VIEW);
            $this->requireUser($tenantId, $userId);
            $query = UserDeviceModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId);
            if (! filter_var($filters['include_revoked'] ?? false, FILTER_VALIDATE_BOOL)) {
                $query->whereNull('revoked_at');
            }
            $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);
            $page = max((int) ($filters['page'] ?? 1), 1);
            $paginator = $query->orderByDesc('last_active_at')->paginate($perPage, ['*'], 'page', $page);
            $items = array_map(fn (mixed $device): DataRecord => $this->record($device), array_values($paginator->items()));
            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function register(int|string $userId, array $payload): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSelf((int) $userId);
            $platform = strtolower(trim((string) ($payload['platform'] ?? '')));
            if (! in_array($platform, UserDevicePlatform::values(), true)) {
                throw new RuntimeException('Device platform is invalid.');
            }
            $plainToken = trim((string) ($payload['device_token'] ?? ''));
            if ($plainToken === '') {
                throw new RuntimeException('Device token is required.');
            }
            $tokenHash = hash('sha256', $plainToken);
            $device = DB::transaction(function () use ($tenantId, $userId, $payload, $platform, $plainToken, $tokenHash): UserDeviceModel {
                $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->lockForUpdate()->first();
                if (! $user instanceof UserModel) {
                    throw new RuntimeException('User not found.');
                }
                $existing = UserDeviceModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())
                    ->where('device_token_hash', $tokenHash)->lockForUpdate()->first();
                $before = $existing?->attributesToArray();
                $device = $existing ?? new UserDeviceModel();
                $device->forceFill([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                    'device_token_hash' => $tokenHash,
                    'device_token_encrypted' => $plainToken,
                    'platform' => $platform,
                    'device_name' => $this->nullableString($payload['device_name'] ?? null),
                    'last_active_at' => $this->clock->now(),
                    'revoked_at' => null,
                    'registered_by_user_id' => $this->actor->currentUserId(),
                    'revoked_by_user_id' => null,
                    'row_version' => $existing instanceof UserDeviceModel
                        ? (int) $existing->getAttribute('row_version') + 1
                        : 1,
                ])->save();
                $this->audit->record('device.registered', 'user_device', $device, $before, $device->attributesToArray());
                return $device;
            }, 3);
            return Result::success($this->record($device));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function touch(int|string $userId, int|string $deviceId, int $expectedVersion): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSelf((int) $userId);
            $device = DB::transaction(function () use ($tenantId, $userId, $deviceId, $expectedVersion): UserDeviceModel {
                $device = $this->find($tenantId, $userId, $deviceId, true);
                if (! $device instanceof UserDeviceModel || $device->getAttribute('revoked_at') !== null) {
                    throw new RuntimeException('Active device not found.');
                }
                $this->assertVersion($device, $expectedVersion);
                $device->forceFill([
                    'last_active_at' => $this->clock->now(),
                    'row_version' => $expectedVersion + 1,
                ])->save();
                return $device;
            }, 3);
            return Result::success($this->record($device));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function revoke(int|string $userId, int|string $deviceId, int $expectedVersion): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DEVICES_MANAGE);
            $device = DB::transaction(function () use ($tenantId, $userId, $deviceId, $expectedVersion): UserDeviceModel {
                $device = $this->find($tenantId, $userId, $deviceId, true);
                if (! $device instanceof UserDeviceModel) {
                    throw new RuntimeException('Device not found.');
                }
                $this->assertVersion($device, $expectedVersion);
                if ($device->getAttribute('revoked_at') !== null) {
                    return $device;
                }
                $before = $device->attributesToArray();
                $device->forceFill([
                    'revoked_at' => $this->clock->now(),
                    'revoked_by_user_id' => $this->actor->currentUserId(),
                    'row_version' => $expectedVersion + 1,
                ])->save();
                $this->audit->record('device.revoked', 'user_device', $device, $before, $device->attributesToArray());
                return $device;
            }, 3);
            return Result::success($this->record($device));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function authorizeSelf(int $subjectUserId): void
    {
        if ($subjectUserId !== $this->actor->currentUserId()) {
            throw new AuthorizationException('A device can only register or report activity for its authenticated owner.');
        }
    }

    private function authorizeSubject(int $subjectUserId, string $permission): void
    {
        if ($subjectUserId === $this->actor->currentUserId()) {
            return;
        }
        if (! $this->authorization->canCurrent($permission)) {
            throw new AuthorizationException('User device access is not authorized.');
        }
    }

    private function requireUser(int $tenantId, int|string $userId): UserModel
    {
        $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->first();
        if (! $user instanceof UserModel) {
            throw new RuntimeException('User not found.');
        }
        return $user;
    }

    private function find(int $tenantId, int|string $userId, int|string $deviceId, bool $lock): ?UserDeviceModel
    {
        $query = UserDeviceModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId)->whereKey($deviceId);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    private function assertVersion(UserDeviceModel $device, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $device->getAttribute('row_version') !== $expectedVersion) {
            throw new RuntimeException('The device changed after it was loaded. Refresh and try again.');
        }
    }

    private function record(UserDeviceModel $device): DataRecord
    {
        return new DataRecord([
            'id' => (int) $device->getKey(),
            'row_version' => (int) $device->getAttribute('row_version'),
            'user_id' => (int) $device->getAttribute('user_id'),
            'platform' => (string) $device->getAttribute('platform'),
            'device_name' => $device->getAttribute('device_name'),
            'last_active_at' => $device->getAttribute('last_active_at')?->toAtomString(),
            'revoked_at' => $device->getAttribute('revoked_at')?->toAtomString(),
            'created_at' => $device->getAttribute('created_at')?->toAtomString(),
        ]);
    }

    private function tenantId(): int
    {
        $id = $this->tenant->currentTenantId();
        if ($id === null) {
            throw new RuntimeException('A tenant context is required.');
        }
        return $id;
    }


    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value === '' ? null : $value;
    }
}
