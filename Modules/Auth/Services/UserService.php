<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Audit\Services\AuditService;
use Modules\Auth\Exceptions\UserNotFoundException;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Exceptions\OrganizationNotFoundException;
use Modules\Tenant\Repositories\OrganizationRepository;
use Modules\Tenant\Services\TenantContext;

/**
 * User Service
 *
 * Handles business logic for user management including creation,
 * updates, deletion, tenant context resolution, and audit logging.
 */
class UserService
{
    /**
     * Create a new UserService instance
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected OrganizationRepository $organizationRepository,
        protected TenantContext $tenantContext,
        protected AuditService $auditService
    ) {}

    /**
     * Create a new user
     *
     * @param  array  $data  User data including name, email, password, etc.
     * @param  string|null  $organizationId  Optional organization ID
     * @return User Created user instance
     *
     * @throws BusinessRuleException When tenant context is missing
     * @throws OrganizationNotFoundException When organization is not found
     */
    public function createUser(array $data, ?string $organizationId = null): User
    {
        $tenantId = $this->resolveTenantId($organizationId);

        $user = TransactionHelper::execute(function () use ($data, $tenantId, $organizationId) {
            $userData = [
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId ?? $data['organization_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
                'metadata' => $data['metadata'] ?? [],
            ];

            $user = $this->userRepository->create($userData);

            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                $this->userRepository->syncRoles($user->id, $data['role_ids']);
            }

            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->userRepository->syncPermissions($user->id, $data['permission_ids']);
            }

            return $user;
        });

        $this->auditService->logEvent(
            'user.created',
            User::class,
            $user->id,
            ['email' => $user->email, 'name' => $user->name]
        );

        return $user->load(['roles', 'permissions', 'organization']);
    }

    /**
     * Update an existing user
     *
     * @param  string  $userId  User ID
     * @param  array  $data  User data to update
     * @return User Updated user instance
     *
     * @throws UserNotFoundException When user is not found
     * @throws OrganizationNotFoundException When organization is not found
     * @throws BusinessRuleException When organization doesn't belong to tenant
     */
    public function updateUser(string $userId, array $data): User
    {
        $user = $this->userRepository->findByIdWithTenant(
            $userId,
            $this->tenantContext->getCurrentTenantId()
        );

        if (! $user) {
            throw new UserNotFoundException("User with ID {$userId} not found.");
        }

        if (isset($data['organization_id']) && $data['organization_id']) {
            $organization = $this->organizationRepository->find($data['organization_id']);
            
            if (! $organization) {
                throw new OrganizationNotFoundException(
                    "Organization with ID {$data['organization_id']} not found."
                );
            }

            if ($organization->tenant_id !== $user->tenant_id) {
                throw new BusinessRuleException(
                    'Organization must belong to the same tenant as the user.'
                );
            }
        }

        $updatedUser = TransactionHelper::execute(function () use ($user, $data) {
            $updateData = [];

            $allowedFields = ['name', 'email', 'organization_id', 'is_active', 'metadata'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if (! empty($updateData)) {
                $user->update($updateData);
            }

            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                $this->userRepository->syncRoles($user->id, $data['role_ids']);
            }

            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->userRepository->syncPermissions($user->id, $data['permission_ids']);
            }

            return $user;
        });

        $this->auditService->logEvent(
            'user.updated',
            User::class,
            $user->id,
            [
                'updated_fields' => array_keys(
                    array_intersect_key($data, array_flip(['name', 'email', 'organization_id', 'is_active']))
                ),
            ]
        );

        return $updatedUser->fresh(['roles', 'permissions', 'organization']);
    }

    /**
     * Delete a user
     *
     * @param  string  $userId  User ID
     * @return void
     *
     * @throws UserNotFoundException When user is not found
     */
    public function deleteUser(string $userId): void
    {
        $user = $this->userRepository->findByIdWithTenant(
            $userId,
            $this->tenantContext->getCurrentTenantId()
        );

        if (! $user) {
            throw new UserNotFoundException("User with ID {$userId} not found.");
        }

        $email = $user->email;

        TransactionHelper::execute(function () use ($user) {
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->devices()->delete();
            $user->delete();
        });

        $this->auditService->logEvent(
            'user.deleted',
            User::class,
            $userId,
            ['email' => $email]
        );
    }

    /**
     * Resolve tenant ID from organization or current context
     *
     * @param  string|null  $organizationId  Optional organization ID
     * @return string Resolved tenant ID
     *
     * @throws OrganizationNotFoundException When organization is not found
     * @throws BusinessRuleException When tenant context is required but missing
     */
    public function resolveTenantId(?string $organizationId): string
    {
        if ($organizationId) {
            $organization = $this->organizationRepository->find($organizationId);
            
            if (! $organization) {
                throw new OrganizationNotFoundException(
                    "Organization with ID {$organizationId} not found."
                );
            }
            
            return $organization->tenant_id;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        return $tenantId;
    }
}
