<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Services\AuditService;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Exceptions\OrganizationNotFoundException;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Repositories\OrganizationRepository;

/**
 * Organization Service
 *
 * Handles complex business logic for hierarchical organization management
 * including CRUD operations, hierarchy manipulation, and descendant updates.
 *
 * CRITICAL FIX: Uses bulk queries for descendant updates instead of recursion
 * to prevent race conditions and performance issues.
 */
class OrganizationService
{
    /**
     * Allowed fields for organization updates
     */
    private const ALLOWED_UPDATE_FIELDS = [
        'name',
        'code',
        'type',
        'metadata',
        'is_active',
    ];

    /**
     * Create a new OrganizationService instance
     */
    public function __construct(
        protected OrganizationRepository $organizationRepository,
        protected AuditService $auditService
    ) {}

    /**
     * Create a new organization
     *
     * @param  array  $data  Organization data including name, code, type, parent_id, etc.
     * @return Organization Created organization instance
     *
     * @throws BusinessRuleException When code is not available or parent is invalid
     */
    public function createOrganization(array $data): Organization
    {
        $tenantId = $data['tenant_id'] ?? null;

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant ID is required.');
        }

        if (isset($data['code']) && ! $this->organizationRepository->isCodeAvailable($tenantId, $data['code'])) {
            throw new BusinessRuleException("Organization with code '{$data['code']}' already exists in this tenant.");
        }

        $parentId = $data['parent_id'] ?? null;

        if ($parentId) {
            $parent = $this->organizationRepository->find($parentId);
            if (! $parent || $parent->tenant_id !== $tenantId) {
                throw new BusinessRuleException('Invalid parent organization.');
            }
        }

        $organization = TransactionHelper::execute(function () use ($data, $parentId) {
            $level = $this->calculateLevel($parentId);

            $organizationData = [
                'tenant_id' => $data['tenant_id'],
                'parent_id' => $parentId,
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'] ?? 'default',
                'metadata' => $data['metadata'] ?? [],
                'level' => $level,
                'is_active' => $data['is_active'] ?? true,
            ];

            return $this->organizationRepository->create($organizationData);
        });

        $this->auditService->logEvent(
            'organization.created',
            Organization::class,
            $organization->id,
            [
                'name' => $organization->name,
                'code' => $organization->code,
                'type' => $organization->type,
                'parent_id' => $organization->parent_id,
            ]
        );

        return $organization->fresh();
    }

    /**
     * Update an existing organization
     *
     * @param  string  $organizationId  Organization ID
     * @param  array  $data  Organization data to update
     * @return Organization Updated organization instance
     *
     * @throws OrganizationNotFoundException When organization is not found
     * @throws BusinessRuleException When code is not available
     */
    public function updateOrganization(string $organizationId, array $data): Organization
    {
        $organization = $this->organizationRepository->find($organizationId);

        if (! $organization) {
            throw new OrganizationNotFoundException("Organization with ID {$organizationId} not found.");
        }

        if (isset($data['code']) && ! $this->organizationRepository->isCodeAvailable($organization->tenant_id, $data['code'], $organizationId)) {
            throw new BusinessRuleException("Organization with code '{$data['code']}' already exists in this tenant.");
        }

        TransactionHelper::execute(function () use ($organization, $data) {
            $updateData = [];

            foreach (self::ALLOWED_UPDATE_FIELDS as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (! empty($updateData)) {
                $this->organizationRepository->update($organization->id, $updateData);
            }
        });

        $this->auditService->logEvent(
            'organization.updated',
            Organization::class,
            $organization->id,
            [
                'updated_fields' => array_keys(
                    array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS))
                ),
            ]
        );

        return $organization->fresh();
    }

    /**
     * Delete an organization (soft delete)
     *
     * @param  string  $organizationId  Organization ID
     * @return void
     *
     * @throws OrganizationNotFoundException When organization is not found
     * @throws BusinessRuleException When organization has active children
     */
    public function deleteOrganization(string $organizationId): void
    {
        $organization = $this->organizationRepository->find($organizationId);

        if (! $organization) {
            throw new OrganizationNotFoundException("Organization with ID {$organizationId} not found.");
        }

        if ($this->organizationRepository->hasChildren($organizationId)) {
            throw new BusinessRuleException(
                'Cannot delete organization with active children. Please delete or move children first.'
            );
        }

        $organizationName = $organization->name;
        $organizationCode = $organization->code;

        TransactionHelper::execute(function () use ($organization) {
            $this->organizationRepository->delete($organization->id);
        });

        $this->auditService->logEvent(
            'organization.deleted',
            Organization::class,
            $organizationId,
            ['name' => $organizationName, 'code' => $organizationCode]
        );
    }

    /**
     * Restore a soft-deleted organization
     *
     * @param  string  $organizationId  Organization ID
     * @return Organization Restored organization instance
     *
     * @throws OrganizationNotFoundException When organization is not found
     * @throws BusinessRuleException When organization is not soft-deleted
     */
    public function restoreOrganization(string $organizationId): Organization
    {
        $organization = $this->organizationRepository->findWithTrashed($organizationId);

        if (! $organization) {
            throw new OrganizationNotFoundException("Organization with ID {$organizationId} not found.");
        }

        if (! $organization->trashed()) {
            throw new BusinessRuleException('Organization is not deleted and cannot be restored.');
        }

        TransactionHelper::execute(function () use ($organizationId) {
            $this->organizationRepository->restore($organizationId);
        });

        $this->auditService->logEvent(
            'organization.restored',
            Organization::class,
            $organization->id,
            ['name' => $organization->name, 'code' => $organization->code]
        );

        return $this->organizationRepository->find($organization->id);
    }

    /**
     * Move organization to a new parent
     *
     * CRITICAL: Uses pessimistic locking to prevent race conditions during move operations
     *
     * @param  string  $organizationId  Organization ID to move
     * @param  string|null  $newParentId  New parent organization ID (null for root)
     * @return Organization Moved organization instance
     *
     * @throws OrganizationNotFoundException When organization or parent is not found
     * @throws BusinessRuleException When move would create circular reference or tenant mismatch
     */
    public function moveOrganization(string $organizationId, ?string $newParentId): Organization
    {
        $organization = $this->organizationRepository->find($organizationId);

        if (! $organization) {
            throw new OrganizationNotFoundException("Organization with ID {$organizationId} not found.");
        }

        if ($newParentId === $organization->parent_id) {
            return $organization;
        }

        if ($newParentId) {
            $newParent = $this->organizationRepository->find($newParentId);

            if (! $newParent) {
                throw new OrganizationNotFoundException("Parent organization with ID {$newParentId} not found.");
            }

            if ($newParent->tenant_id !== $organization->tenant_id) {
                throw new BusinessRuleException('Cannot move organization to a parent in a different tenant.');
            }

            if ($this->organizationRepository->isDescendantOf($newParentId, $organizationId)) {
                throw new BusinessRuleException('Cannot move organization to one of its descendants.');
            }
        }

        TransactionHelper::withLock(function () use ($organization, $newParentId) {
            $oldLevel = $organization->level;
            $newLevel = $this->calculateLevel($newParentId);
            $levelDifference = $newLevel - $oldLevel;

            $this->organizationRepository->update($organization->id, [
                'parent_id' => $newParentId,
                'level' => $newLevel,
            ]);

            if ($levelDifference !== 0) {
                $this->updateDescendantLevels($organizationId, $levelDifference);
            }
        }, 'organizations', $organizationId);

        $this->auditService->logEvent(
            'organization.moved',
            Organization::class,
            $organization->id,
            [
                'old_parent_id' => $organization->parent_id,
                'new_parent_id' => $newParentId,
            ]
        );

        return $organization->fresh();
    }

    /**
     * Calculate organization level based on parent
     *
     * @param  string|null  $parentId  Parent organization ID
     * @return int Organization level (0 for root)
     */
    public function calculateLevel(?string $parentId): int
    {
        if (! $parentId) {
            return 0;
        }

        $parent = $this->organizationRepository->find($parentId);

        if (! $parent) {
            return 0;
        }

        return $parent->level + 1;
    }

    /**
     * Update levels for all descendants using bulk query
     *
     * CRITICAL FIX: This replaces the recursive approach (lines 354-366 in OrganizationController)
     * which had race condition risks. Now uses a single bulk update query.
     *
     * The bulk query approach:
     * - Eliminates N+1 query problem
     * - Prevents race conditions from concurrent updates
     * - Significantly faster for large hierarchies
     * - Atomic operation within transaction
     *
     * @param  string  $organizationId  Parent organization ID
     * @param  int  $levelDifference  Amount to adjust descendant levels
     * @return void
     */
    public function updateDescendantLevels(string $organizationId, int $levelDifference): void
    {
        if ($levelDifference === 0) {
            return;
        }

        $organization = $this->organizationRepository->find($organizationId);

        if (! $organization) {
            return;
        }

        $descendantIds = $this->organizationRepository->getDescendantIds($organizationId, false);
        
        if (! empty($descendantIds)) {
            Organization::whereIn('id', $descendantIds)
                ->increment('level', $levelDifference);
        }
    }
}
