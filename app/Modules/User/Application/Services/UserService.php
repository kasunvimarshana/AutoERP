<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Modules\User\Application\Actions\DeleteUserRecordAction;
use Modules\User\Application\Actions\FindUserRecordAction;
use Modules\User\Application\Actions\ListUserRecordsAction;
use Modules\User\Application\Actions\PersistUserRecordAction;
use Modules\User\Application\DTOs\PermissionData;
use Modules\User\Application\DTOs\RoleData;
use Modules\User\Application\DTOs\RolePermissionData;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserDeviceData;
use Modules\User\Application\DTOs\UserDocumentData;
use Modules\User\Application\DTOs\UserPermissionData;
use Modules\User\Application\DTOs\UserRoleData;
use Modules\User\Application\DTOs\UserTenantData;
use Modules\User\Application\Repositories\PermissionRepositoryInterface;
use Modules\User\Application\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Application\Repositories\RoleRepositoryInterface;
use Modules\User\Application\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Application\Repositories\UserDocumentRepositoryInterface;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserRoleRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Domain\Services\UserDomainService;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
        private readonly RolePermissionRepositoryInterface $rolePermissions,
        private readonly UserRoleRepositoryInterface $userRoles,
        private readonly UserPermissionRepositoryInterface $userPermissions,
        private readonly UserTenantRepositoryInterface $userTenants,
        private readonly UserDocumentRepositoryInterface $userDocuments,
        private readonly UserDeviceRepositoryInterface $userDevices,
        private readonly UserDomainService $domain,
        private readonly ListUserRecordsAction $listRecords,
        private readonly FindUserRecordAction $findRecord,
        private readonly PersistUserRecordAction $persistRecord,
        private readonly DeleteUserRecordAction $deleteRecord,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUsers(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->users, $filters, $perPage);
    }

    public function findUser(int|string $id): Model
    {
        return $this->findRecord->execute($this->users, 'User', $id);
    }

    public function createUser(UserData $data): Model
    {
        return $this->persistRecord->create($this->users, $this->userAttributes($data));
    }

    public function updateUser(int|string $id, UserData $data): Model
    {
        return $this->persistRecord->update($this->users, $this->findUser($id), $this->userAttributes($data, false));
    }

    public function deleteUser(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->users, $this->findUser($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listRoles(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->roles, $filters, $perPage);
    }

    public function findRole(int|string $id): Model
    {
        return $this->findRecord->execute($this->roles, 'Role', $id);
    }

    public function createRole(RoleData $data): Model
    {
        return $this->persistRecord->create($this->roles, $this->roleAttributes($data));
    }

    public function updateRole(int|string $id, RoleData $data): Model
    {
        return $this->persistRecord->update($this->roles, $this->findRole($id), $this->roleAttributes($data));
    }

    public function deleteRole(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->roles, $this->findRole($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPermissions(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->permissions, $filters, $perPage);
    }

    public function findPermission(int|string $id): Model
    {
        return $this->findRecord->execute($this->permissions, 'Permission', $id);
    }

    public function createPermission(PermissionData $data): Model
    {
        return $this->persistRecord->create($this->permissions, $this->permissionAttributes($data));
    }

    public function updatePermission(int|string $id, PermissionData $data): Model
    {
        return $this->persistRecord->update($this->permissions, $this->findPermission($id), $this->permissionAttributes($data));
    }

    public function deletePermission(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->permissions, $this->findPermission($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listRolePermissions(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->rolePermissions, $filters, $perPage);
    }

    public function findRolePermission(int|string $id): Model
    {
        return $this->findRecord->execute($this->rolePermissions, 'Role permission', $id);
    }

    public function createRolePermission(RolePermissionData $data): Model
    {
        return $this->persistRecord->create($this->rolePermissions, $this->rolePermissionAttributes($data));
    }

    public function updateRolePermission(int|string $id, RolePermissionData $data): Model
    {
        return $this->persistRecord->update($this->rolePermissions, $this->findRolePermission($id), $this->rolePermissionAttributes($data));
    }

    public function deleteRolePermission(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->rolePermissions, $this->findRolePermission($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUserRoles(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->userRoles, $filters, $perPage);
    }

    public function findUserRole(int|string $id): Model
    {
        return $this->findRecord->execute($this->userRoles, 'User role', $id);
    }

    public function createUserRole(UserRoleData $data): Model
    {
        return $this->persistRecord->create($this->userRoles, $this->userRoleAttributes($data));
    }

    public function updateUserRole(int|string $id, UserRoleData $data): Model
    {
        return $this->persistRecord->update($this->userRoles, $this->findUserRole($id), $this->userRoleAttributes($data));
    }

    public function deleteUserRole(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->userRoles, $this->findUserRole($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUserPermissions(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->userPermissions, $filters, $perPage);
    }

    public function findUserPermission(int|string $id): Model
    {
        return $this->findRecord->execute($this->userPermissions, 'User permission', $id);
    }

    public function createUserPermission(UserPermissionData $data): Model
    {
        return $this->persistRecord->create($this->userPermissions, $this->userPermissionAttributes($data));
    }

    public function updateUserPermission(int|string $id, UserPermissionData $data): Model
    {
        return $this->persistRecord->update($this->userPermissions, $this->findUserPermission($id), $this->userPermissionAttributes($data));
    }

    public function deleteUserPermission(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->userPermissions, $this->findUserPermission($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUserTenants(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->userTenants, $filters, $perPage);
    }

    public function findUserTenant(int|string $id): Model
    {
        return $this->findRecord->execute($this->userTenants, 'User tenant', $id);
    }

    public function createUserTenant(UserTenantData $data): Model
    {
        return $this->persistRecord->create($this->userTenants, $this->userTenantAttributes($data));
    }

    public function updateUserTenant(int|string $id, UserTenantData $data): Model
    {
        return $this->persistRecord->update($this->userTenants, $this->findUserTenant($id), $this->userTenantAttributes($data));
    }

    public function deleteUserTenant(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->userTenants, $this->findUserTenant($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUserDocuments(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->userDocuments, $filters, $perPage);
    }

    public function findUserDocument(int|string $id): Model
    {
        return $this->findRecord->execute($this->userDocuments, 'User document', $id);
    }

    public function createUserDocument(UserDocumentData $data): Model
    {
        return $this->persistRecord->create($this->userDocuments, $this->userDocumentAttributes($data));
    }

    public function updateUserDocument(int|string $id, UserDocumentData $data): Model
    {
        return $this->persistRecord->update($this->userDocuments, $this->findUserDocument($id), $this->userDocumentAttributes($data));
    }

    public function deleteUserDocument(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->userDocuments, $this->findUserDocument($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUserDevices(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->userDevices, $filters, $perPage);
    }

    public function findUserDevice(int|string $id): Model
    {
        return $this->findRecord->execute($this->userDevices, 'User device', $id);
    }

    public function createUserDevice(UserDeviceData $data): Model
    {
        return $this->persistRecord->create($this->userDevices, $this->userDeviceAttributes($data));
    }

    public function updateUserDevice(int|string $id, UserDeviceData $data): Model
    {
        return $this->persistRecord->update($this->userDevices, $this->findUserDevice($id), $this->userDeviceAttributes($data));
    }

    public function deleteUserDevice(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->userDevices, $this->findUserDevice($id));
    }

    /**
     * @return array<string, mixed>
     */
    private function userAttributes(UserData $data, bool $requirePassword = true): array
    {
        $this->domain->assertUserStatus($data->status);

        $attributes = [
            'first_name' => $this->domain->normalizeText($data->firstName),
            'last_name' => $this->domain->normalizeText($data->lastName),
            'email' => $this->domain->normalizeEmail($data->email),
            'email_verified_at' => $data->emailVerifiedAt,
            'status' => $data->status,
            'avatar_path' => $this->domain->normalizeText($data->avatarPath),
            'phone' => $this->domain->normalizeText($data->phone),
            'preferences' => $this->domain->normalizeMetadata($data->preferences),
            'date_of_birth' => $data->dateOfBirth,
            'gender' => $this->domain->normalizeText($data->gender),
            'marital_status' => $this->domain->normalizeText($data->maritalStatus),
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];

        if ($data->password !== null && $data->password !== '') {
            $attributes['password'] = Hash::make($data->password);
        } elseif ($requirePassword) {
            throw new InvalidArgumentException('Password is required.');
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function roleAttributes(RoleData $data): array
    {
        return [
            'name' => $this->domain->normalizeText($data->name),
            'guard_name' => $this->domain->normalizeGuardName($data->guardName),
            'description' => $this->domain->normalizeText($data->description),
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permissionAttributes(PermissionData $data): array
    {
        return [
            'name' => $this->domain->normalizeText($data->name),
            'guard_name' => $this->domain->normalizeGuardName($data->guardName),
            'module' => $this->domain->normalizeText($data->module),
            'description' => $this->domain->normalizeText($data->description),
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rolePermissionAttributes(RolePermissionData $data): array
    {
        return [
            'role_id' => $data->roleId,
            'permission_id' => $data->permissionId,
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userRoleAttributes(UserRoleData $data): array
    {
        return [
            'user_id' => $data->userId,
            'role_id' => $data->roleId,
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPermissionAttributes(UserPermissionData $data): array
    {
        return [
            'user_id' => $data->userId,
            'permission_id' => $data->permissionId,
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userTenantAttributes(UserTenantData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'user_id' => $data->userId,
            'role_id' => $data->roleId,
            'is_default' => $data->isDefault,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userDocumentAttributes(UserDocumentData $data): array
    {
        return [
            'user_id' => $data->userId,
            'name' => $this->domain->normalizeText($data->name),
            'file_path' => $this->domain->normalizeText($data->filePath),
            'mime_type' => $this->domain->normalizeText($data->mimeType),
            'size' => $data->size,
            'type' => $this->domain->normalizeText($data->type),
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userDeviceAttributes(UserDeviceData $data): array
    {
        return [
            'user_id' => $data->userId,
            'device_token' => $this->domain->normalizeText($data->deviceToken),
            'platform' => $this->domain->normalizeText($data->platform),
            'device_name' => $this->domain->normalizeText($data->deviceName),
            'last_active_at' => $data->lastActiveAt,
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
