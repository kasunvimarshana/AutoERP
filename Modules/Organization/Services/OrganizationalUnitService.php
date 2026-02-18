<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Entities\OrganizationalUnit;
use Modules\Organization\Repositories\Contracts\OrganizationalUnitRepositoryInterface;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;

class OrganizationalUnitService
{
    public function __construct(
        private OrganizationalUnitRepositoryInterface $unitRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Create a new organizational unit.
     */
    public function createUnit(array $data): OrganizationalUnit
    {
        DB::beginTransaction();
        try {
            // Validate organization exists
            $organization = $this->organizationRepository->findById($data['organization_id']);
            if (!$organization) {
                throw new \Exception("Organization not found");
            }

            // Validate location exists and belongs to organization if provided
            if (isset($data['location_id'])) {
                $location = $this->locationRepository->findById($data['location_id']);
                if (!$location) {
                    throw new \Exception("Location not found");
                }
                if ($location->organization_id !== $data['organization_id']) {
                    throw new \Exception("Location does not belong to the specified organization");
                }
            }

            // Validate parent unit exists and belongs to same organization
            if (isset($data['parent_unit_id'])) {
                $parent = $this->unitRepository->findById($data['parent_unit_id']);
                if (!$parent) {
                    throw new \Exception("Parent organizational unit not found");
                }
                if ($parent->organization_id !== $data['organization_id']) {
                    throw new \Exception("Parent unit must belong to the same organization");
                }

                // Prevent circular references
                if ($this->wouldCreateCircularReference($data['parent_unit_id'], null)) {
                    throw new \Exception("Cannot create circular reference in organizational unit hierarchy");
                }
            }

            // Ensure code is unique
            if ($this->unitRepository->findByCode($data['code'])) {
                throw new \Exception("Organizational unit code already exists");
            }

            $unit = $this->unitRepository->create($data);

            DB::commit();
            return $unit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an organizational unit.
     */
    public function updateUnit(int $id, array $data): OrganizationalUnit
    {
        DB::beginTransaction();
        try {
            $unit = $this->unitRepository->findById($id);
            
            if (!$unit) {
                throw new \Exception("Organizational unit not found");
            }

            // Validate organization change
            if (isset($data['organization_id']) && $data['organization_id'] !== $unit->organization_id) {
                $organization = $this->organizationRepository->findById($data['organization_id']);
                if (!$organization) {
                    throw new \Exception("Organization not found");
                }

                // Check if unit has children - prevent org change if it has children
                if ($unit->children()->count() > 0) {
                    throw new \Exception("Cannot change organization of unit with children");
                }
            }

            // Validate location change
            if (isset($data['location_id'])) {
                $location = $this->locationRepository->findById($data['location_id']);
                if (!$location) {
                    throw new \Exception("Location not found");
                }
                $orgId = $data['organization_id'] ?? $unit->organization_id;
                if ($location->organization_id !== $orgId) {
                    throw new \Exception("Location does not belong to the unit's organization");
                }
            }

            // Validate parent change
            if (isset($data['parent_unit_id']) && $data['parent_unit_id'] !== $unit->parent_unit_id) {
                // Cannot set self as parent
                if ($data['parent_unit_id'] === $id) {
                    throw new \Exception("Organizational unit cannot be its own parent");
                }

                // Validate parent exists and belongs to same organization
                if ($data['parent_unit_id'] !== null) {
                    $parent = $this->unitRepository->findById($data['parent_unit_id']);
                    if (!$parent) {
                        throw new \Exception("Parent organizational unit not found");
                    }
                    $orgId = $data['organization_id'] ?? $unit->organization_id;
                    if ($parent->organization_id !== $orgId) {
                        throw new \Exception("Parent unit must belong to the same organization");
                    }

                    // Prevent circular references
                    if ($this->wouldCreateCircularReference($data['parent_unit_id'], $id)) {
                        throw new \Exception("Cannot create circular reference in organizational unit hierarchy");
                    }
                }
            }

            // Validate code uniqueness if changed
            if (isset($data['code']) && $data['code'] !== $unit->code) {
                if ($this->unitRepository->findByCode($data['code'])) {
                    throw new \Exception("Organizational unit code already exists");
                }
            }

            $unit = $this->unitRepository->update($unit, $data);

            DB::commit();
            return $unit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an organizational unit.
     */
    public function deleteUnit(int $id): bool
    {
        DB::beginTransaction();
        try {
            $unit = $this->unitRepository->findById($id);
            
            if (!$unit) {
                throw new \Exception("Organizational unit not found");
            }

            // Check if unit has children
            if ($unit->children()->count() > 0) {
                throw new \Exception("Cannot delete organizational unit with children. Delete or reassign children first.");
            }

            $result = $this->unitRepository->delete($unit);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get organizational unit hierarchy.
     */
    public function getHierarchy(int $unitId): OrganizationalUnit
    {
        $unit = $this->unitRepository->findById($unitId);
        
        if (!$unit) {
            throw new \Exception("Organizational unit not found");
        }

        $unit->load(['children' => function ($query) {
            $query->with('children');
        }]);

        return $unit;
    }

    /**
     * Get all units for an organization as a tree.
     */
    public function getOrganizationTree(int $organizationId): Collection
    {
        $organization = $this->organizationRepository->findById($organizationId);
        
        if (!$organization) {
            throw new \Exception("Organization not found");
        }

        $units = $this->unitRepository->getByOrganization($organizationId);
        return OrganizationalUnit::buildTree($units);
    }

    /**
     * Get all root units (units without parents).
     */
    public function getRootUnits(): Collection
    {
        return $this->unitRepository->getRoots();
    }

    /**
     * Get all active units.
     */
    public function getActiveUnits(): Collection
    {
        return $this->unitRepository->getActive();
    }

    /**
     * Search organizational units.
     */
    public function searchUnits(string $query): Collection
    {
        return $this->unitRepository->search($query);
    }

    /**
     * Check if setting parent would create a circular reference.
     */
    private function wouldCreateCircularReference(?int $parentId, ?int $unitId): bool
    {
        if (!$parentId) {
            return false;
        }

        $parent = $this->unitRepository->findById($parentId);
        if (!$parent) {
            return false;
        }

        // If updating existing unit, check if parent is a descendant
        if ($unitId) {
            $unit = $this->unitRepository->findById($unitId);
            if ($unit && $parent->isDescendantOf($unit)) {
                return true;
            }
        }

        return false;
    }
}
