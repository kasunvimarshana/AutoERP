<?php

declare(strict_types=1);

namespace Modules\User\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\TenantEntitlementService;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Repositories\UserRepositoryInterface;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserService extends AbstractUserCrudService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserOrganizationUnitRepositoryInterface $userOrganizationUnits,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantEntitlementService $entitlements,
        private readonly UserDomainServiceInterface $domain,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $perPage = max(1, (int) ($filters['per_page'] ?? 15));
            $page = max(1, (int) ($filters['page'] ?? 1));
            $tenantId = $this->resolveTenantId($this->toNullableInt($filters['tenant_id'] ?? null));
            $search = $this->domain->normalizeNullableString((string) ($filters['search'] ?? ''));
            $status = $this->domain->normalizeNullableString($filters['status'] ?? null);
            $roleId = $this->toNullableInt($filters['role_id'] ?? null);
            $organizationUnitId = $this->toNullableInt(
                $filters['organization_unit_filter_id'] ?? $filters['organization_unit_id'] ?? null,
            );
            if ($organizationUnitId !== null) {
                $this->resolveOrganizationUnitId($tenantId, $organizationUnitId);
            }

            $result = $this->users->pageByFilters(
                $tenantId,
                $search,
                $status,
                $roleId,
                $organizationUnitId,
                $perPage,
                $page,
            );

            return $this->success(new PagedResult(
                array_map(fn (DataRecord $record): DataRecord => $this->withUserRelations($record), $result->items),
                $result->total,
                $result->page,
                $result->perPage,
            ));
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.list');
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $tenantId = $this->resolveTenantId(null);
            $record = $this->users->findById($id);
            if ($record === null || (int) $record->require('tenant_id') !== $tenantId) {
                return $this->notFound('User not found.');
            }

            return $this->success($this->withUserRelations($record));
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.get', ['user_id' => (string) $id]);
        }
    }

    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->resolveTenantId($this->toNullableInt($payload['tenant_id'] ?? null));
                $this->tenants->lockById($tenantId);
                $userLimit = $this->entitlements->limit($tenantId, 'max_users');
                if ($userLimit !== null && $this->users->countByTenant($tenantId) >= $userLimit) {
                    return $this->failure(
                        UserErrorCode::PLAN_LIMIT_REACHED,
                        'The tenant plan user limit has been reached.',
                    );
                }

                $email = $this->domain->normalizeEmail((string) ($payload['email'] ?? ''));
                $username = $this->normalizeUsername($payload['username'] ?? null);
                $firstName = $this->domain->normalizeRequiredString(
                    (string) ($payload['first_name'] ?? ''),
                    'First name',
                );

                if ($this->users->findByTenantAndEmail($tenantId, $email) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
                }
                if ($username !== null && $this->users->findByTenantAndUsername($tenantId, $username) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_USERNAME, 'Username already exists in tenant scope.');
                }

                $organizationUnitId = $this->resolveOrganizationUnitId(
                    $tenantId,
                    $this->toNullableInt($payload['default_organization_unit_id'] ?? null),
                );
                $roleIds = $this->validateRoleIds($tenantId, $payload['role_ids'] ?? []);
                $organizationUnitIds = $this->validateOrganizationUnitIds(
                    $tenantId,
                    $payload['organization_unit_ids'] ?? [],
                    $organizationUnitId,
                );

                $metadata = $this->domain->normalizeMetadata($payload['metadata'] ?? null);
                $metadata = $this->mergeIdentityReferences($metadata, $payload['identity_references'] ?? null);

                $created = $this->users->create([
                    'tenant_id' => $tenantId,
                    'metadata' => $metadata,
                    'first_name' => $firstName,
                    'last_name' => $this->domain->normalizeNullableString($payload['last_name'] ?? null),
                    'username' => $username,
                    'email' => $email,
                    'email_verified_at' => $this->domain->normalizeNullableString(
                        $payload['email_verified_at'] ?? null,
                    ),
                    'password' => $this->passwordHasher->hash((string) ($payload['password'] ?? '')),
                    'status' => $this->domain->normalizeStatus($payload['status'] ?? null),
                    'avatar_path' => $this->domain->normalizeNullableString($payload['avatar_path'] ?? null),
                    'phone' => $this->domain->normalizeNullableString($payload['phone'] ?? null),
                    'preferences' => $this->domain->normalizeMetadata($payload['preferences'] ?? null),
                    'date_of_birth' => $this->domain->normalizeNullableString($payload['date_of_birth'] ?? null),
                    'gender' => $this->domain->normalizeNullableString($payload['gender'] ?? null),
                    'marital_status' => $this->domain->normalizeNullableString($payload['marital_status'] ?? null),
                    'row_version' => 1,
                ]);

                $this->syncUserRoles((int) $created->id(), $tenantId, $roleIds);
                $this->syncOrganizationAccess((int) $created->id(), $tenantId, $organizationUnitIds, $organizationUnitId);

                return $this->success($this->getCreatedOrUpdatedUser($created));
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.create');
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                $targetId = (int) $existing->id();
                $tenantId = (int) $existing->require('tenant_id');

                if (
                    array_key_exists('row_version', $payload)
                    && (int) $payload['row_version'] !== (int) $existing->get('row_version', 1)
                ) {
                    return $this->failure(
                        UserErrorCode::STALE_RECORD,
                        'User was changed by someone else. Reload before saving.',
                    );
                }

                if (array_key_exists('password', $payload)) {
                    return $this->failure(
                        UserErrorCode::INVALID_VALUE,
                        'Password cannot be changed through the ordinary user edit workflow.',
                    );
                }

                if (
                    array_key_exists('tenant_id', $payload)
                    && $this->toNullableInt($payload['tenant_id']) !== $tenantId
                ) {
                    return $this->failure(UserErrorCode::TENANT_MISMATCH, 'User tenant cannot be changed.');
                }

                $email = array_key_exists('email', $payload)
                    ? $this->domain->normalizeEmail((string) $payload['email'])
                    : (string) $existing->get('email');
                $username = array_key_exists('username', $payload)
                    ? $this->normalizeUsername($payload['username'])
                    : $this->normalizeUsername($existing->get('username'));
                $firstName = array_key_exists('first_name', $payload)
                    ? $this->domain->normalizeRequiredString((string) $payload['first_name'], 'First name')
                    : (string) $existing->get('first_name');

                if ($this->users->findByTenantAndEmail($tenantId, $email, $targetId) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
                }
                if (
                    $username !== null
                    && $this->users->findByTenantAndUsername($tenantId, $username, $targetId) !== null
                ) {
                    return $this->failure(UserErrorCode::DUPLICATE_USERNAME, 'Username already exists in tenant scope.');
                }

                $organizationUnitId = array_key_exists('default_organization_unit_id', $payload)
                    ? $this->toNullableInt($payload['default_organization_unit_id'])
                    : $this->defaultOrganizationUnitId($tenantId, $targetId);
                $organizationUnitId = $this->resolveOrganizationUnitId($tenantId, $organizationUnitId);
                $roleIds = array_key_exists('role_ids', $payload)
                    ? $this->validateRoleIds($tenantId, $payload['role_ids'])
                    : null;
                $organizationUnitIds = array_key_exists('organization_unit_ids', $payload)
                    || array_key_exists('default_organization_unit_id', $payload)
                        ? $this->validateOrganizationUnitIds(
                            $tenantId,
                            $payload['organization_unit_ids'] ?? [],
                            $organizationUnitId,
                        )
                        : null;

                $metadata = $this->domain->normalizeMetadata($existing->get('metadata'));
                $status = array_key_exists('status', $payload)
                    ? $this->domain->normalizeStatus($payload['status'])
                    : (string) $existing->get('status');

                if ($status !== (string) $existing->get('status')) {
                    $statusGuard = $this->guardStatusChange($existing, $status);
                    if ($statusGuard instanceof Result) {
                        return $statusGuard;
                    }
                }

                $updated = $this->users->update($id, [
                    'tenant_id' => $tenantId,
                    'metadata' => $metadata,
                    'first_name' => $firstName,
                    'last_name' => array_key_exists('last_name', $payload)
                        ? $this->domain->normalizeNullableString($payload['last_name'])
                        : $existing->get('last_name'),
                    'username' => $username,
                    'email' => $email,
                    'email_verified_at' => $existing->get('email_verified_at'),
                    'status' => $status,
                    'avatar_path' => array_key_exists('avatar_path', $payload)
                        ? $this->domain->normalizeNullableString($payload['avatar_path'])
                        : $existing->get('avatar_path'),
                    'phone' => array_key_exists('phone', $payload)
                        ? $this->domain->normalizeNullableString($payload['phone'])
                        : $existing->get('phone'),
                    'preferences' => array_key_exists('preferences', $payload)
                        ? $this->domain->normalizeMetadata($payload['preferences'])
                        : $existing->get('preferences'),
                    'date_of_birth' => array_key_exists('date_of_birth', $payload)
                        ? $this->domain->normalizeNullableString($payload['date_of_birth'])
                        : $existing->get('date_of_birth'),
                    'gender' => array_key_exists('gender', $payload)
                        ? $this->domain->normalizeNullableString($payload['gender'])
                        : $existing->get('gender'),
                    'marital_status' => array_key_exists('marital_status', $payload)
                        ? $this->domain->normalizeNullableString($payload['marital_status'])
                        : $existing->get('marital_status'),
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]);

                if ($roleIds !== null) {
                    $roleGuard = $this->guardRoleSync($existing, $tenantId, $roleIds);
                    if ($roleGuard instanceof Result) {
                        return $roleGuard;
                    }

                    $this->syncUserRoles($targetId, $tenantId, $roleIds);
                }

                if ($organizationUnitIds !== null) {
                    $this->syncOrganizationAccess($targetId, $tenantId, $organizationUnitIds, $organizationUnitId);
                }

                if ($status !== UserStatus::ACTIVE) {
                    $this->revokeUserAuthArtifacts($targetId, $tenantId);
                }

                return $this->success($this->getCreatedOrUpdatedUser($updated));
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.update', ['user_id' => (string) $id]);
        }
    }

    public function activate(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::ACTIVE);
    }

    public function deactivate(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::INACTIVE);
    }

    public function suspend(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::SUSPENDED);
    }

    public function assignUserToOrganizationUnit(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $user = $this->users->findById($id);
                if ($user === null || ! $this->isInTenantScope($user)) {
                    return $this->notFound('User not found.');
                }

                $tenantId = (int) $user->require('tenant_id');
                $organizationUnitId = $this->resolveOrganizationUnitId(
                    $tenantId,
                    $this->toNullableInt($payload['organization_unit_id'] ?? null),
                );
                if ($organizationUnitId === null) {
                    return $this->failure(
                        UserErrorCode::ORGANIZATION_UNIT_NOT_FOUND,
                        'Organization unit identifier is required.',
                    );
                }

                $existingAssignment = $this->userOrganizationUnits->findAssignment(
                    $tenantId,
                    $organizationUnitId,
                    (int) $user->id(),
                );
                if ($existingAssignment !== null) {
                    return $this->failure(
                        UserErrorCode::DUPLICATE_ORGANIZATION_ASSIGNMENT,
                        'User is already assigned to this organization unit.',
                    );
                }

                $isDefault = $this->toBool($payload['is_default'] ?? false)
                    || $this->defaultOrganizationUnitId($tenantId, (int) $user->id()) === null;
                if ($isDefault) {
                    $this->userOrganizationUnits->clearDefaultForUser($tenantId, (int) $user->id());
                }

                $assignment = $this->userOrganizationUnits->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'user_id' => (int) $user->id(),
                    'status' => UserOrganizationUnitStatus::ACTIVE,
                    'is_default' => $isDefault,
                    'default_marker' => $isDefault ? 'default' : null,
                    'row_version' => 1,
                ]);

                return $this->success($assignment);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.assign-organization', ['user_id' => (string) $id]);
        }
    }

    public function removeUserFromOrganizationUnit(int|string $id, int|string $organizationUnitId): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $organizationUnitId): Result {
                $user = $this->users->findById($id);
                if ($user === null || ! $this->isInTenantScope($user)) {
                    return $this->notFound('User not found.');
                }

                $tenantId = (int) $user->require('tenant_id');
                $orgId = $this->toNullableInt($organizationUnitId);
                if ($orgId === null) {
                    return $this->failure(
                        UserErrorCode::ORGANIZATION_UNIT_NOT_FOUND,
                        'Organization unit identifier is invalid.',
                    );
                }

                $assignment = $this->userOrganizationUnits->findAssignment($tenantId, $orgId, (int) $user->id());
                if ($assignment === null) {
                    return $this->failure(UserErrorCode::ASSIGNMENT_NOT_FOUND, 'Organization assignment not found.');
                }

                $removedDefault = (bool) $assignment->get('is_default', false);
                $this->userOrganizationUnits->deleteAssignment(
                    $assignment->id(),
                    $tenantId,
                    (int) $user->id(),
                );

                if ($removedDefault) {
                    $replacement = $this->userOrganizationUnits->firstActiveForTenantAndUser(
                        $tenantId,
                        (int) $user->id(),
                    );
                    if ($replacement !== null) {
                        $this->userOrganizationUnits->setDefault(
                            $tenantId,
                            (int) $user->id(),
                            (int) $replacement->require('organization_unit_id'),
                        );
                    }
                }

                return $this->success(true);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.remove-organization', [
                'user_id' => (string) $id,
                'organization_unit_id' => (string) $organizationUnitId,
            ]);
        }
    }

    public function resolveByIdentity(string $providerKey, string $providerUserKey): Result
    {
        try {
            $tenantId = $this->resolveTenantId(null);
            $normalizedProviderKey = trim(strtolower($providerKey));
            $normalizedProviderUserKey = trim($providerUserKey);
            if ($normalizedProviderKey === '' || $normalizedProviderUserKey === '') {
                return $this->failure(
                    UserErrorCode::IDENTITY_REFERENCE_INVALID,
                    'Identity provider key and user key are required.',
                );
            }

            $record = $this->users->findByTenantAndIdentityReference(
                $tenantId,
                $normalizedProviderKey,
                $normalizedProviderUserKey,
            );
            if ($record === null) {
                return $this->notFound('User not found for given identity reference.');
            }

            return $this->success($record);
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.resolve-identity', [
                'provider_key' => $providerKey,
            ]);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                $guard = $this->guardStatusChange($existing, UserStatus::INACTIVE);
                if ($guard instanceof Result) {
                    return $guard;
                }

                if (! $this->users->delete($id)) {
                    return $this->notFound('User not found.');
                }

                $this->revokeUserAuthArtifacts((int) $existing->id(), (int) $existing->require('tenant_id'));

                return $this->success(true);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.delete', ['user_id' => (string) $id]);
        }
    }

    private function setStatus(int|string $id, string $status): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $status): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                $guard = $this->guardStatusChange($existing, $status);
                if ($guard instanceof Result) {
                    return $guard;
                }

                $record = $this->users->update($id, [
                    'status' => $status,
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]);

                if ($status !== UserStatus::ACTIVE) {
                    $this->revokeUserAuthArtifacts((int) $existing->id(), (int) $existing->require('tenant_id'));
                }

                return $this->success($this->getCreatedOrUpdatedUser($record));
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.status', [
                'user_id' => (string) $id,
                'status' => $status,
            ]);
        }
    }

    private function isInTenantScope(DataRecord $record): bool
    {
        $tenantId = $this->resolveTenantId(null);

        return (int) $record->require('tenant_id') === $tenantId;
    }

    private function normalizeUsername(mixed $value): ?string
    {
        $username = strtolower(trim((string) $value));

        return $username === '' ? null : $username;
    }

    private function getCreatedOrUpdatedUser(DataRecord $record): DataRecord
    {
        $fresh = $this->users->findById($record->id());

        return $this->withUserRelations($fresh ?? $record);
    }

    private function withUserRelations(DataRecord $record): DataRecord
    {
        $userId = (int) $record->id();
        $tenantId = $this->toNullableInt($record->get('tenant_id'));
        $payload = $record->toArray();
        unset($payload['password'], $payload['remember_token'], $payload['organization_unit_id']);

        $payload['name'] = trim(implode(' ', array_filter([
            $payload['first_name'] ?? null,
            $payload['last_name'] ?? null,
        ]))) ?: ($payload['username'] ?? $payload['email'] ?? null);

        if ($tenantId !== null) {
            $payload['roles'] = DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.tenant_id', $tenantId)
                ->where('user_roles.user_id', $userId)
                ->whereNull('roles.deleted_at')
                ->orderBy('roles.name')
                ->get(['roles.id', 'roles.name', 'roles.description'])
                ->map(static fn (object $row): array => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'description' => $row->description !== null ? (string) $row->description : null,
                ])
                ->values()
                ->all();

            $payload['organization_units'] = DB::table('user_organization_units')
                ->leftJoin('organization_units', 'organization_units.id', '=', 'user_organization_units.organization_unit_id')
                ->where('user_organization_units.tenant_id', $tenantId)
                ->where('user_organization_units.user_id', $userId)
                ->whereNull('organization_units.deleted_at')
                ->orderByDesc('user_organization_units.is_default')
                ->orderBy('organization_units.name')
                ->get([
                    'organization_units.id',
                    'organization_units.name',
                    'organization_units.code',
                    'user_organization_units.is_default',
                ])
                ->map(static fn (object $row): array => [
                    'id' => $row->id !== null ? (int) $row->id : null,
                    'name' => $row->name !== null ? (string) $row->name : null,
                    'code' => $row->code !== null ? (string) $row->code : null,
                    'is_default' => (bool) $row->is_default,
                ])
                ->filter(static fn (array $row): bool => $row['id'] !== null)
                ->values()
                ->all();
            $payload['default_organization_unit_id'] = collect($payload['organization_units'])
                ->firstWhere('is_default', true)['id'] ?? null;

            $directPermissions = DB::table('user_permissions')
                ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
                ->where('user_permissions.tenant_id', $tenantId)
                ->where('user_permissions.user_id', $userId)
                ->whereNull('permissions.deleted_at')
                ->get(['permissions.id', 'permissions.name', 'permissions.module', 'permissions.description']);
            $rolePermissions = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->join('user_roles', 'user_roles.role_id', '=', 'role_permissions.role_id')
                ->where('role_permissions.tenant_id', $tenantId)
                ->where('user_roles.tenant_id', $tenantId)
                ->where('user_roles.user_id', $userId)
                ->whereNull('permissions.deleted_at')
                ->get(['permissions.id', 'permissions.name', 'permissions.module', 'permissions.description']);
            $permissionMap = [];
            foreach ($directPermissions->merge($rolePermissions) as $row) {
                $permissionMap[(int) $row->id] = [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'module' => $row->module !== null ? (string) $row->module : null,
                    'description' => $row->description !== null ? (string) $row->description : null,
                ];
            }
            $payload['permissions'] = array_values($permissionMap);

            if (Schema::hasTable('auth_sessions')) {
                $payload['last_login_at'] = DB::table('auth_sessions')
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->max('last_activity_at');
            }
        }

        $payload['roles'] ??= [];
        $payload['organization_units'] ??= [];
        $payload['default_organization_unit_id'] ??= null;
        $payload['permissions'] ??= [];
        $payload['last_login_at'] ??= null;

        return new DataRecord($payload);
    }

    /**
     * @return list<int>
     */
    private function validateRoleIds(int $tenantId, mixed $value): array
    {
        $roleIds = $this->normalizeIdList($value);
        if ($roleIds === []) {
            return [];
        }

        $availableRoleIds = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        sort($roleIds);
        sort($availableRoleIds);

        if ($roleIds !== $availableRoleIds) {
            throw new InvalidArgumentException('One or more selected roles are not available for this tenant.');
        }

        return $roleIds;
    }

    /**
     * @return list<int>
     */
    private function validateOrganizationUnitIds(int $tenantId, mixed $value, ?int $defaultOrganizationUnitId): array
    {
        $organizationUnitIds = $this->normalizeIdList($value);
        if ($defaultOrganizationUnitId !== null && ! in_array($defaultOrganizationUnitId, $organizationUnitIds, true)) {
            $organizationUnitIds[] = $defaultOrganizationUnitId;
        }

        $organizationUnitIds = array_values(array_unique($organizationUnitIds));
        if ($organizationUnitIds === []) {
            return [];
        }

        foreach ($organizationUnitIds as $organizationUnitId) {
            $this->resolveOrganizationUnitId($tenantId, $organizationUnitId);
        }

        return $organizationUnitIds;
    }

    /**
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Identifier list must be an array.');
        }

        $ids = [];
        foreach ($value as $entry) {
            $id = $this->toNullableInt($entry);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function syncUserRoles(int $userId, int $tenantId, array $roleIds): void
    {
        $existingRoleIds = DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $removeIds = array_values(array_diff($existingRoleIds, $roleIds));
        if ($removeIds !== []) {
            DB::table('user_roles')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->whereIn('role_id', $removeIds)
                ->delete();
        }

        $addIds = array_values(array_diff($roleIds, $existingRoleIds));
        foreach ($addIds as $roleId) {
            DB::table('user_roles')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->currentOrganizationUnit->currentOrganizationUnitId(),
                'user_id' => $userId,
                'role_id' => $roleId,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  list<int>  $organizationUnitIds
     */
    private function syncOrganizationAccess(
        int $userId,
        int $tenantId,
        array $organizationUnitIds,
        ?int $defaultOrganizationUnitId,
    ): void {
        $existing = DB::table('user_organization_units')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get(['id', 'organization_unit_id']);

        $existingByOrganizationUnit = [];
        foreach ($existing as $row) {
            if ($row->organization_unit_id !== null) {
                $existingByOrganizationUnit[(int) $row->organization_unit_id] = (int) $row->id;
            }
        }

        $removeIds = array_values(array_diff(array_keys($existingByOrganizationUnit), $organizationUnitIds));
        if ($removeIds !== []) {
            DB::table('user_organization_units')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->whereIn('organization_unit_id', $removeIds)
                ->delete();
        }

        $defaultOrganizationUnitId ??= $organizationUnitIds[0] ?? null;
        if ($defaultOrganizationUnitId !== null) {
            DB::table('user_organization_units')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->update(['is_default' => false, 'default_marker' => null, 'updated_at' => now()]);
        }

        foreach ($organizationUnitIds as $organizationUnitId) {
            DB::table('user_organization_units')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'user_id' => $userId,
                ],
                [
                    'status' => UserOrganizationUnitStatus::ACTIVE,
                    'is_default' => $organizationUnitId === $defaultOrganizationUnitId,
                    'default_marker' => $organizationUnitId === $defaultOrganizationUnitId ? 'default' : null,
                    'row_version' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function defaultOrganizationUnitId(int $tenantId, int $userId): ?int
    {
        $default = $this->userOrganizationUnits->findDefaultForTenantAndUser($tenantId, $userId);

        return $default === null
            ? null
            : $this->toNullableInt($default->get('organization_unit_id'));
    }

    private function guardStatusChange(DataRecord $target, string $nextStatus): ?Result
    {
        if ($nextStatus === UserStatus::ACTIVE) {
            return null;
        }

        $targetUserId = (int) $target->id();
        $tenantId = (int) $target->require('tenant_id');
        if ($this->currentUser->currentUserId() === $targetUserId) {
            return $this->failure(
                UserErrorCode::PROTECTED_ACCOUNT,
                'You cannot deactivate or delete your own active account.',
            );
        }

        if (
            strtolower((string) $target->get('status')) === UserStatus::ACTIVE
            && $this->hasProtectedAdminRole($targetUserId, $tenantId)
            && $this->activeProtectedAdminCount($tenantId) <= 1
        ) {
            return $this->failure(
                UserErrorCode::LAST_ADMIN,
                'At least one active protected administrator must remain.',
            );
        }

        return null;
    }

    /**
     * @param  list<int>  $nextRoleIds
     */
    private function guardRoleSync(DataRecord $target, int $tenantId, array $nextRoleIds): ?Result
    {
        $targetUserId = (int) $target->id();
        if (! $this->hasProtectedAdminRole($targetUserId, $tenantId)) {
            return null;
        }

        if ($this->roleIdsIncludeProtectedAdmin($tenantId, $nextRoleIds)) {
            return null;
        }

        if ($this->currentUser->currentUserId() === $targetUserId) {
            return $this->failure(
                UserErrorCode::PROTECTED_ACCOUNT,
                'You cannot remove your own final protected administrator role.',
            );
        }

        if (
            strtolower((string) $target->get('status')) === UserStatus::ACTIVE
            && $this->activeProtectedAdminCount($tenantId) <= 1
        ) {
            return $this->failure(
                UserErrorCode::LAST_ADMIN,
                'At least one active protected administrator must remain.',
            );
        }

        return null;
    }

    private function hasProtectedAdminRole(int $userId, int $tenantId): bool
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.tenant_id', $tenantId)
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', UserPermission::SUPER_ADMIN_ROLE)
            ->whereNull('roles.deleted_at')
            ->exists();
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function roleIdsIncludeProtectedAdmin(int $tenantId, array $roleIds): bool
    {
        if ($roleIds === []) {
            return false;
        }

        return DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->whereNull('deleted_at')
            ->exists();
    }

    private function activeProtectedAdminCount(int $tenantId): int
    {
        return DB::table('users')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('users.status', UserStatus::ACTIVE)
            ->where('users.tenant_id', $tenantId)
            ->where('user_roles.tenant_id', $tenantId)
            ->where('roles.name', UserPermission::SUPER_ADMIN_ROLE)
            ->whereNull('users.deleted_at')
            ->whereNull('roles.deleted_at')
            ->distinct('users.id')
            ->count('users.id');
    }

    private function revokeUserAuthArtifacts(int $userId, int $tenantId): void
    {
        $now = now();

        if (Schema::hasTable('auth_sessions')) {
            DB::table('auth_sessions')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable('auth_access_tokens')) {
            DB::table('auth_access_tokens')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable('auth_refresh_tokens')) {
            DB::table('auth_refresh_tokens')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]);
        }
    }

    private function resolveTenantId(?int $requestedTenantId): int
    {
        $currentTenantId = $this->currentTenant->currentTenantId();
        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            throw new InvalidArgumentException('Tenant scope mismatch for user operation.');
        }

        $resolvedTenantId = $requestedTenantId ?? $currentTenantId;
        if ($resolvedTenantId === null || $resolvedTenantId < 1) {
            throw new InvalidArgumentException('Tenant context is required for user operations.');
        }

        return $resolvedTenantId;
    }

    private function resolveOrganizationUnitId(int $tenantId, ?int $organizationUnitId): ?int
    {
        $contextOrganizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();
        $resolvedOrganizationUnitId = $organizationUnitId ?? $contextOrganizationUnitId;
        if ($resolvedOrganizationUnitId === null) {
            return null;
        }

        $record = $this->organizationUnits->findById($resolvedOrganizationUnitId);
        if ($record === null) {
            throw new InvalidArgumentException('Organization unit not found.');
        }

        if ((int) $record->require('tenant_id') !== $tenantId) {
            throw new InvalidArgumentException('Organization unit must belong to same tenant as user.');
        }

        return $resolvedOrganizationUnitId;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function mergeIdentityReferences(?array $metadata, mixed $identityReferences): ?array
    {
        if ($identityReferences === null) {
            return $metadata;
        }

        if (! is_array($identityReferences)) {
            throw new InvalidArgumentException('Identity references must be an object map.');
        }

        $normalizedReferences = [];
        foreach ($identityReferences as $providerKey => $providerUserKey) {
            if (! is_string($providerKey) || trim($providerKey) === '') {
                throw new InvalidArgumentException('Identity provider key must be a non-empty string.');
            }

            if (! is_scalar($providerUserKey) || trim((string) $providerUserKey) === '') {
                throw new InvalidArgumentException('Identity provider user key must be a non-empty scalar value.');
            }

            $normalizedReferences[strtolower(trim($providerKey))] = trim((string) $providerUserKey);
        }

        $base = $metadata ?? [];
        $existingReferences = $base['identity_references'] ?? [];
        if (! is_array($existingReferences)) {
            $existingReferences = [];
        }

        $base['identity_references'] = array_merge($existingReferences, $normalizedReferences);

        return $base;
    }

    /**
     * @param  array<string, scalar|array|null>  $context
     */
    private function normalizeFailure(Throwable $exception, string $operation, array $context = []): Result
    {
        return Result::failure($this->errorNormalizer->normalize(
            $exception,
            UserErrorCode::INVALID_VALUE,
            array_merge(['operation' => $operation], $context),
        ));
    }
}
