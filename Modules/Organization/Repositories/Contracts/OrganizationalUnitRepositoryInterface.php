<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\OrganizationalUnit;

interface OrganizationalUnitRepositoryInterface
{
    /**
     * Find organizational unit by ID.
     */
    public function findById(int $id): ?OrganizationalUnit;

    /**
     * Find organizational unit by code.
     */
    public function findByCode(string $code): ?OrganizationalUnit;

    /**
     * Get all organizational units.
     */
    public function all(): Collection;

    /**
     * Get organizational units with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new organizational unit.
     */
    public function create(array $data): OrganizationalUnit;

    /**
     * Update an existing organizational unit.
     */
    public function update(OrganizationalUnit $unit, array $data): OrganizationalUnit;

    /**
     * Delete an organizational unit.
     */
    public function delete(OrganizationalUnit $unit): bool;

    /**
     * Get root organizational units (no parent).
     */
    public function getRoots(): Collection;

    /**
     * Get children of an organizational unit.
     */
    public function getChildren(int $unitId): Collection;

    /**
     * Get all descendants of an organizational unit.
     */
    public function getDescendants(int $unitId): Collection;

    /**
     * Get organizational units by organization.
     */
    public function getByOrganization(int $organizationId): Collection;

    /**
     * Get organizational units by location.
     */
    public function getByLocation(int $locationId): Collection;

    /**
     * Get organizational units by type.
     */
    public function getByType(string $type): Collection;

    /**
     * Get active organizational units.
     */
    public function getActive(): Collection;

    /**
     * Get organizational units by manager.
     */
    public function getByManager(int $managerId): Collection;

    /**
     * Search organizational units by name or code.
     */
    public function search(string $query): Collection;
}
