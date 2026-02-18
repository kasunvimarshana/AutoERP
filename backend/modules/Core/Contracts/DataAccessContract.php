<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Data Access Contract for AutoERP Multi-Tenant System
 *
 * Enforces tenant-aware data operations across all ERP modules.
 * All implementations must respect tenant boundaries and audit requirements.
 */
interface DataAccessContract
{
    /**
     * Retrieve entity within current tenant scope
     */
    public function fetchById(int|string $entityId): ?Model;

    /**
     * Retrieve entity or throw tenant-aware exception
     */
    public function fetchByIdOrFail(int|string $entityId): Model;

    /**
     * List entities with tenant-aware filtering
     */
    public function fetchFiltered(array $criteria = [], ?int $limit = null): iterable;

    /**
     * Persist new entity with tenant context
     */
    public function persist(array $entityData): Model;

    /**
     * Modify existing entity within tenant scope
     */
    public function modify(int|string $entityId, array $changes): Model;

    /**
     * Remove entity (soft delete with audit trail)
     */
    public function remove(int|string $entityId): bool;

    /**
     * Count entities matching criteria within tenant
     */
    public function countMatching(array $criteria = []): int;

    /**
     * Check entity existence within tenant scope
     */
    public function existsInTenant(int|string $entityId): bool;

    /**
     * Search entities by field criteria
     */
    public function searchByField(string $fieldName, mixed $fieldValue): ?Model;

    /**
     * Bulk create entities with tenant validation
     */
    public function bulkPersist(array $entitiesData): bool;
}
