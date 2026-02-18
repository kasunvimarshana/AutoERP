<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Entities\Organization;

interface OrganizationRepositoryInterface
{
    /**
     * Find organization by ID.
     */
    public function findById(int $id): ?Organization;

    /**
     * Find organization by code.
     */
    public function findByCode(string $code): ?Organization;

    /**
     * Get all organizations.
     */
    public function all(): Collection;

    /**
     * Get organizations with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new organization.
     */
    public function create(array $data): Organization;

    /**
     * Update an existing organization.
     */
    public function update(Organization $organization, array $data): Organization;

    /**
     * Delete an organization.
     */
    public function delete(Organization $organization): bool;

    /**
     * Get root organizations (no parent).
     */
    public function getRoots(): Collection;

    /**
     * Get children of an organization.
     */
    public function getChildren(int $organizationId): Collection;

    /**
     * Get all descendants of an organization.
     */
    public function getDescendants(int $organizationId): Collection;

    /**
     * Get organizations by type.
     */
    public function getByType(string $type): Collection;

    /**
     * Get active organizations.
     */
    public function getActive(): Collection;

    /**
     * Search organizations by name or code.
     */
    public function search(string $query): Collection;
}
