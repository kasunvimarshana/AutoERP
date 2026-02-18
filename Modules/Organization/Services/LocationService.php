<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Entities\Location;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;

class LocationService
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Create a new location.
     */
    public function createLocation(array $data): Location
    {
        DB::beginTransaction();
        try {
            // Validate organization exists
            $organization = $this->organizationRepository->findById($data['organization_id']);
            if (!$organization) {
                throw new \Exception("Organization not found");
            }

            // Validate parent location exists if provided
            if (isset($data['parent_location_id'])) {
                $parent = $this->locationRepository->findById($data['parent_location_id']);
                if (!$parent) {
                    throw new \Exception("Parent location not found");
                }

                // Parent must belong to same organization
                if ($parent->organization_id !== $data['organization_id']) {
                    throw new \Exception("Parent location must belong to the same organization");
                }
            }

            // Ensure code is unique
            if ($this->locationRepository->findByCode($data['code'])) {
                throw new \Exception("Location code already exists");
            }

            $location = $this->locationRepository->create($data);

            DB::commit();
            return $location;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a location.
     */
    public function updateLocation(int $id, array $data): Location
    {
        DB::beginTransaction();
        try {
            $location = $this->locationRepository->findById($id);
            
            if (!$location) {
                throw new \Exception("Location not found");
            }

            // Validate organization change
            if (isset($data['organization_id']) && $data['organization_id'] !== $location->organization_id) {
                $organization = $this->organizationRepository->findById($data['organization_id']);
                if (!$organization) {
                    throw new \Exception("Organization not found");
                }
            }

            // Validate parent location change
            if (isset($data['parent_location_id']) && $data['parent_location_id'] !== $location->parent_location_id) {
                if ($data['parent_location_id'] === $id) {
                    throw new \Exception("Location cannot be its own parent");
                }

                $parent = $this->locationRepository->findById($data['parent_location_id']);
                if (!$parent) {
                    throw new \Exception("Parent location not found");
                }

                // Parent must belong to same organization
                $orgId = $data['organization_id'] ?? $location->organization_id;
                if ($parent->organization_id !== $orgId) {
                    throw new \Exception("Parent location must belong to the same organization");
                }
            }

            // Validate code uniqueness if changed
            if (isset($data['code']) && $data['code'] !== $location->code) {
                if ($this->locationRepository->findByCode($data['code'])) {
                    throw new \Exception("Location code already exists");
                }
            }

            $location = $this->locationRepository->update($location, $data);

            DB::commit();
            return $location;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a location.
     */
    public function deleteLocation(int $id): bool
    {
        DB::beginTransaction();
        try {
            $location = $this->locationRepository->findById($id);
            
            if (!$location) {
                throw new \Exception("Location not found");
            }

            // Check if location has children
            if ($location->children()->count() > 0) {
                throw new \Exception("Cannot delete location with child locations");
            }

            $result = $this->locationRepository->delete($location);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get location hierarchy tree for an organization.
     */
    public function getLocationTree(int $organizationId, ?int $rootLocationId = null): Collection
    {
        $locations = $this->locationRepository->getByOrganization($organizationId);

        return Location::buildTree($locations, $rootLocationId);
    }
}
