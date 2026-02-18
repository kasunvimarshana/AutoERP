<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;

class OrganizationService
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Create a new organization.
     */
    public function createOrganization(array $data): Organization
    {
        DB::beginTransaction();
        try {
            // Validate parent exists if provided
            if (isset($data['parent_id'])) {
                $parent = $this->organizationRepository->findById($data['parent_id']);
                if (!$parent) {
                    throw new \Exception("Parent organization not found");
                }

                // Prevent circular references
                if ($this->wouldCreateCircularReference($data['parent_id'], null)) {
                    throw new \Exception("Cannot create circular reference in organization hierarchy");
                }
            }

            // Ensure code is unique
            if ($this->organizationRepository->findByCode($data['code'])) {
                throw new \Exception("Organization code already exists");
            }

            $organization = $this->organizationRepository->create($data);

            DB::commit();
            return $organization;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an organization.
     */
    public function updateOrganization(int $id, array $data): Organization
    {
        DB::beginTransaction();
        try {
            $organization = $this->organizationRepository->findById($id);
            
            if (!$organization) {
                throw new \Exception("Organization not found");
            }

            // Validate parent change
            if (isset($data['parent_id']) && $data['parent_id'] !== $organization->parent_id) {
                // Cannot set self as parent
                if ($data['parent_id'] === $id) {
                    throw new \Exception("Organization cannot be its own parent");
                }

                // Prevent circular references
                if ($this->wouldCreateCircularReference($data['parent_id'], $id)) {
                    throw new \Exception("Cannot create circular reference in organization hierarchy");
                }
            }

            // Validate code uniqueness if changed
            if (isset($data['code']) && $data['code'] !== $organization->code) {
                if ($this->organizationRepository->findByCode($data['code'])) {
                    throw new \Exception("Organization code already exists");
                }
            }

            $organization = $this->organizationRepository->update($organization, $data);

            DB::commit();
            return $organization;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an organization.
     */
    public function deleteOrganization(int $id): bool
    {
        DB::beginTransaction();
        try {
            $organization = $this->organizationRepository->findById($id);
            
            if (!$organization) {
                throw new \Exception("Organization not found");
            }

            // Check if organization has children
            if ($organization->children()->count() > 0) {
                throw new \Exception("Cannot delete organization with child organizations");
            }

            // Check if organization has locations
            if ($organization->locations()->count() > 0) {
                throw new \Exception("Cannot delete organization with associated locations");
            }

            $result = $this->organizationRepository->delete($organization);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get organization hierarchy tree.
     */
    public function getHierarchyTree(?int $rootId = null): Collection
    {
        if ($rootId) {
            $root = $this->organizationRepository->findById($rootId);
            if (!$root) {
                throw new \Exception("Root organization not found");
            }
            $organizations = $root->descendants()->push($root);
        } else {
            $organizations = $this->organizationRepository->all();
        }

        return Organization::buildTree($organizations, $rootId);
    }

    /**
     * Check if setting a parent would create a circular reference.
     */
    private function wouldCreateCircularReference(?int $parentId, ?int $childId): bool
    {
        if (!$parentId || !$childId) {
            return false;
        }

        $parent = $this->organizationRepository->findById($parentId);
        if (!$parent) {
            return false;
        }

        // Check if parent is a descendant of child
        return $parent->isDescendantOf($this->organizationRepository->findById($childId));
    }
}
