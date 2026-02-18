<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\Location;

interface LocationRepositoryInterface
{
    /**
     * Find location by ID.
     */
    public function findById(int $id): ?Location;

    /**
     * Find location by code.
     */
    public function findByCode(string $code): ?Location;

    /**
     * Get all locations.
     */
    public function all(): Collection;

    /**
     * Get locations with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new location.
     */
    public function create(array $data): Location;

    /**
     * Update an existing location.
     */
    public function update(Location $location, array $data): Location;

    /**
     * Delete a location.
     */
    public function delete(Location $location): bool;

    /**
     * Get locations by organization.
     */
    public function getByOrganization(int $organizationId): Collection;

    /**
     * Get locations by type.
     */
    public function getByType(string $type): Collection;

    /**
     * Get active locations.
     */
    public function getActive(): Collection;

    /**
     * Search locations by name or code.
     */
    public function search(string $query): Collection;
}
