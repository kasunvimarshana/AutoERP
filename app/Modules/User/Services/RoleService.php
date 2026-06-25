<?php

declare(strict_types=1);

namespace Modules\User\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Constants\UserPermission;
use Modules\User\Repositories\RoleRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class RoleService extends AbstractUserCrudService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly UserDomainServiceInterface $domain,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $perPage = max(1, (int) ($filters['per_page'] ?? 15));
            $page = max(1, (int) ($filters['page'] ?? 1));
            $tenantId = $this->resolveTenantId($this->toNullableInt($filters['tenant_id'] ?? null));
            $search = $this->domain->normalizeNullableString((string) ($filters['search'] ?? ''));
            $result = $this->roles->pageByFilters($tenantId, $search, $perPage, $page);

            return $this->success(new PagedResult(
                array_map(fn (DataRecord $record): DataRecord => $this->withRoleRelations($record), $result->items),
                $result->total,
                $result->page,
                $result->perPage,
            ));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->roles->findById($id);
            if ($record === null || (int) $record->require('tenant_id') !== $this->resolveTenantId(null)) {
                return $this->notFound('Role not found.');
            }

            return $this->success($this->withRoleRelations($record));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->resolveTenantId($this->toNullableInt($payload['tenant_id'] ?? null));
                $name = $this->domain->normalizeRequiredString((string) ($payload['name'] ?? ''), 'Role name');
                $guardName = $this->domain->normalizeNullableString($payload['guard_name'] ?? null)
                    ?? (string) config('auth.defaults.guard', 'api');

                if ($this->roles->findByTenantNameGuard($tenantId, $name, $guardName) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_ROLE, 'Role already exists in tenant scope.');
                }

                $permissionIds = $this->validatePermissionIds($tenantId, $payload['permission_ids'] ?? []);
                $created = $this->roles->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'name' => $name,
                    'guard_name' => $guardName,
                    'description' => $this->domain->normalizeNullableString($payload['description'] ?? null),
                    'row_version' => 1,
                ]);
                $this->syncRolePermissions((int) $created->id(), $tenantId, $permissionIds);

                return $this->success($this->getCreatedOrUpdatedRole($created));
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->roles->findById($id);
            if ($existing === null || (int) $existing->require('tenant_id') !== $this->resolveTenantId(null)) {
                return $this->notFound('Role not found.');
            }

            return $this->transactions->runInTransaction(function () use ($id, $payload, $existing): Result {
                if ($this->isProtectedRole($existing)) {
                    return $this->failure(
                        UserErrorCode::PROTECTED_ACCOUNT,
                        'Protected system roles cannot be modified.',
                    );
                }

                if (
                    array_key_exists('row_version', $payload)
                    && (int) $payload['row_version'] !== (int) $existing->get('row_version', 1)
                ) {
                    return $this->failure(
                        UserErrorCode::STALE_RECORD,
                        'Role was changed by someone else. Reload before saving.',
                    );
                }

                $recordId = (int) $existing->id();
                $tenantId = (int) $existing->require('tenant_id');
                if (
                    array_key_exists('tenant_id', $payload)
                    && $this->toNullableInt($payload['tenant_id']) !== $tenantId
                ) {
                    return $this->failure(UserErrorCode::TENANT_MISMATCH, 'Role tenant cannot be changed.');
                }

                $name = array_key_exists('name', $payload)
                    ? $this->domain->normalizeRequiredString((string) $payload['name'], 'Role name')
                    : (string) $existing->get('name');
                $guardName = array_key_exists('guard_name', $payload)
                    ? ($this->domain->normalizeNullableString($payload['guard_name']) ?? 'api')
                    : (string) $existing->get('guard_name', 'api');

                if ($this->roles->findByTenantNameGuard($tenantId, $name, $guardName, $recordId) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_ROLE, 'Role already exists in tenant scope.');
                }

                $updated = $this->roles->update($id, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'metadata' => array_key_exists('metadata', $payload)
                        ? $this->domain->normalizeMetadata($payload['metadata'])
                        : $existing->get('metadata'),
                    'name' => $name,
                    'guard_name' => $guardName,
                    'description' => array_key_exists('description', $payload)
                        ? $this->domain->normalizeNullableString($payload['description'])
                        : $existing->get('description'),
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]);

                if (array_key_exists('permission_ids', $payload)) {
                    $this->syncRolePermissions(
                        $recordId,
                        $tenantId,
                        $this->validatePermissionIds($tenantId, $payload['permission_ids']),
                    );
                }

                return $this->success($this->getCreatedOrUpdatedRole($updated));
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            $existing = $this->roles->findById($id);
            if ($existing === null || (int) $existing->require('tenant_id') !== $this->resolveTenantId(null)) {
                return $this->notFound('Role not found.');
            }

            if ($this->isProtectedRole($existing)) {
                return $this->failure(UserErrorCode::PROTECTED_ACCOUNT, 'Protected system roles cannot be deleted.');
            }

            if (DB::table('user_roles')
                ->where('tenant_id', (int) $existing->require('tenant_id'))
                ->where('role_id', (int) $existing->id())
                ->exists()) {
                return $this->failure(UserErrorCode::INVALID_VALUE, 'Role is assigned to users and cannot be deleted.');
            }

            return $this->transactions->runInTransaction(function () use ($id, $existing): Result {
                DB::table('role_permissions')
                    ->where('tenant_id', (int) $existing->require('tenant_id'))
                    ->where('role_id', (int) $existing->id())
                    ->delete();

                if (! $this->roles->delete($id)) {
                    return $this->notFound('Role not found.');
                }

                return $this->success(true);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function resolveTenantId(?int $requestedTenantId): int
    {
        $currentTenantId = $this->currentTenant->currentTenantId();
        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            throw new InvalidArgumentException('Tenant scope mismatch for role operation.');
        }

        $resolvedTenantId = $requestedTenantId ?? $currentTenantId;
        if ($resolvedTenantId === null || $resolvedTenantId < 1) {
            throw new InvalidArgumentException('Tenant context is required for role operations.');
        }

        return $resolvedTenantId;
    }

    private function getCreatedOrUpdatedRole(DataRecord $record): DataRecord
    {
        $fresh = $this->roles->findById($record->id());

        return $this->withRoleRelations($fresh ?? $record);
    }

    private function withRoleRelations(DataRecord $record): DataRecord
    {
        $roleId = (int) $record->id();
        $tenantId = (int) $record->require('tenant_id');
        $payload = $record->toArray();
        $payload['code'] = $payload['guard_name'] ?? null;
        $payload['status'] = $this->isProtectedRole($record) ? 'protected' : 'active';
        $payload['assigned_users_count'] = DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->distinct('user_id')
            ->count('user_id');
        $payload['permissions_count'] = DB::table('role_permissions')
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->count();
        $payload['permissions'] = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.tenant_id', $tenantId)
            ->where('role_permissions.role_id', $roleId)
            ->whereNull('permissions.deleted_at')
            ->orderBy('permissions.module')
            ->orderBy('permissions.name')
            ->get(['permissions.id', 'permissions.name', 'permissions.module', 'permissions.description'])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'module' => $row->module !== null ? (string) $row->module : null,
                'description' => $row->description !== null ? (string) $row->description : null,
            ])
            ->values()
            ->all();

        return new DataRecord($payload);
    }

    /**
     * @return list<int>
     */
    private function validatePermissionIds(int $tenantId, mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Permission list must be an array.');
        }

        $permissionIds = [];
        foreach ($value as $entry) {
            $id = $this->toNullableInt($entry);
            if ($id !== null) {
                $permissionIds[] = $id;
            }
        }

        $permissionIds = array_values(array_unique($permissionIds));
        if ($permissionIds === []) {
            return [];
        }

        $availablePermissionIds = DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $permissionIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        sort($permissionIds);
        sort($availablePermissionIds);

        if ($permissionIds !== $availablePermissionIds) {
            throw new InvalidArgumentException('One or more selected permissions are not available for this tenant.');
        }

        return $permissionIds;
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function syncRolePermissions(int $roleId, int $tenantId, array $permissionIds): void
    {
        $existingPermissionIds = DB::table('role_permissions')
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $removeIds = array_values(array_diff($existingPermissionIds, $permissionIds));
        if ($removeIds !== []) {
            DB::table('role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $removeIds)
                ->delete();
        }

        $addIds = array_values(array_diff($permissionIds, $existingPermissionIds));
        foreach ($addIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function isProtectedRole(DataRecord $role): bool
    {
        return trim((string) $role->get('name')) === UserPermission::SUPER_ADMIN_ROLE;
    }
}
