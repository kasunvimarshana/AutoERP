<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\RoleModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class RoleService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly UserAccessResolver $access,
        private readonly UserAuditService $audit,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $this->requirePermission(UserPermission::ROLES_VIEW);
            $query = $this->baseQuery()->where('tenant_id', $this->tenantId());
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where('name', 'like', $search.'%');
            }
            $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);
            $page = max((int) ($filters['page'] ?? 1), 1);
            $paginator = $query->orderByDesc('is_system')->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);
            $items = array_map(fn (mixed $role): DataRecord => $this->record($role), array_values($paginator->items()));
            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $this->requirePermission(UserPermission::ROLES_VIEW);
            $role = $this->baseQuery()->where('tenant_id', $this->tenantId())->whereKey($id)->first();
            return $role instanceof RoleModel ? Result::success($this->record($role)) : $this->notFound('Role not found.');
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $this->requirePermission(UserPermission::ROLES_CREATE);
            $tenantId = $this->tenantId();
            $role = DB::transaction(function () use ($tenantId, $payload): RoleModel {
                $this->tenantLock->lock($tenantId);
                $name = trim((string) ($payload['name'] ?? ''));
                $this->assertUniqueName($tenantId, $name, null);
                $role = RoleModel::query()->create([
                    'tenant_id' => $tenantId,
                    'row_version' => 1,
                    'name' => $name,
                    'guard_name' => UserGuard::TENANT_API,
                    'system_key' => null,
                    'is_system' => false,
                    'description' => $this->nullableString($payload['description'] ?? null),
                    'created_by_user_id' => $this->actor->currentUserId(),
                    'updated_by_user_id' => $this->actor->currentUserId(),
                ]);
                $this->audit->record('role.created', 'role', $role, null, $role->attributesToArray());
                return $role;
            }, 3);
            return Result::success($this->record($this->baseQuery()->whereKey($role->getKey())->firstOrFail()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $this->requirePermission(UserPermission::ROLES_UPDATE);
            $tenantId = $this->tenantId();
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            $role = DB::transaction(function () use ($tenantId, $id, $payload, $expectedVersion): RoleModel {
                $this->tenantLock->lock($tenantId);
                $role = RoleModel::query()->where('tenant_id', $tenantId)->whereKey($id)->lockForUpdate()->first();
                if (! $role instanceof RoleModel) {
                    throw new RuntimeException('Role not found.');
                }
                $this->assertVersion($role, $expectedVersion);
                if ((bool) $role->getAttribute('is_system')) {
                    throw new RuntimeException('System roles are immutable.');
                }
                $before = $role->attributesToArray();
                $name = array_key_exists('name', $payload) ? trim((string) $payload['name']) : (string) $role->getAttribute('name');
                $this->assertUniqueName($tenantId, $name, (int) $role->getKey());
                $role->forceFill([
                    'name' => $name,
                    'description' => array_key_exists('description', $payload)
                        ? $this->nullableString($payload['description']) : $role->getAttribute('description'),
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $this->actor->currentUserId(),
                ])->save();
                $this->access->forgetForRoleTenant((int) $role->getKey(), $tenantId);
                $this->audit->record('role.updated', 'role', $role, $before, $role->attributesToArray());
                return $role;
            }, 3);
            return Result::success($this->record($this->baseQuery()->whereKey($role->getKey())->firstOrFail()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id, int $expectedVersion): Result
    {
        try {
            $this->requirePermission(UserPermission::ROLES_DELETE);
            $tenantId = $this->tenantId();
            DB::transaction(function () use ($tenantId, $id, $expectedVersion): void {
                $this->tenantLock->lock($tenantId);
                $role = RoleModel::query()->where('tenant_id', $tenantId)->whereKey($id)->lockForUpdate()->first();
                if (! $role instanceof RoleModel) {
                    throw new RuntimeException('Role not found.');
                }
                $this->assertVersion($role, $expectedVersion);
                if ((bool) $role->getAttribute('is_system')) {
                    throw new RuntimeException('System roles cannot be archived.');
                }
                if ($role->users()->select('user_roles.id')->lockForUpdate()->first() !== null) {
                    throw new RuntimeException('Remove this role from all users before archiving it.');
                }
                $before = $role->attributesToArray();
                $role->forceFill([
                    'deleted_at' => now(),
                    'active_name_key' => null,
                    'deleted_by_user_id' => $this->actor->currentUserId(),
                    'row_version' => $expectedVersion + 1,
                ])->save();
                $this->access->forgetForRoleTenant((int) $role->getKey(), $tenantId);
                $this->audit->record('role.archived', 'role', $role, $before, null);
            }, 3);
            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function baseQuery(): Builder
    {
        return RoleModel::query()
            ->with(['permissions.permission:id,tenant_id,name,module,description,is_active'])
            ->withCount(['users as assigned_users_count', 'permissions as permissions_count']);
    }

    private function record(RoleModel $role): DataRecord
    {
        return new DataRecord([
            'id' => (int) $role->getKey(),
            'row_version' => (int) $role->getAttribute('row_version'),
            'name' => (string) $role->getAttribute('name'),
            'guard_name' => (string) $role->getAttribute('guard_name'),
            'description' => $role->getAttribute('description'),
            'system_key' => $role->getAttribute('system_key'),
            'is_system' => (bool) $role->getAttribute('is_system'),
            'assigned_users_count' => (int) ($role->getAttribute('assigned_users_count') ?? 0),
            'permissions_count' => (int) ($role->getAttribute('permissions_count') ?? 0),
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions->map(static fn ($assignment): ?array => $assignment->permission === null ? null : [
                    'id' => (int) $assignment->permission->getKey(),
                    'name' => (string) $assignment->permission->getAttribute('name'),
                    'module' => (string) $assignment->permission->getAttribute('module'),
                    'description' => $assignment->permission->getAttribute('description'),
                ])->filter()->values()->all()
                : [],
        ]);
    }

    private function assertUniqueName(int $tenantId, string $name, ?int $excludingId): void
    {
        if ($name === '') {
            throw new RuntimeException('Role name is required.');
        }
        $query = RoleModel::query()->where('tenant_id', $tenantId)->where('guard_name', UserGuard::TENANT_API)
            ->where('active_name_key', mb_strtolower($name));
        if ($excludingId !== null) {
            $query->where($query->getModel()->getKeyName(), '!=', $excludingId);
        }
        if ($query->exists()) {
            throw new RuntimeException('An active role with this name already exists.');
        }
    }

    private function assertVersion(RoleModel $role, int $expected): void
    {
        if ($expected < 1 || (int) $role->getAttribute('row_version') !== $expected) {
            throw new RuntimeException('The role changed after it was loaded. Refresh and try again.');
        }
    }

    private function requirePermission(string $permission): void
    {
        if (! $this->authorization->canCurrent($permission)) {
            throw new AuthorizationException('This role action is not authorized.');
        }
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
