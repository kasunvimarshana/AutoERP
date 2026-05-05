<?php declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\Asset;

/**
 * AssetRepositoryInterface - Contract for Asset persistence
 *
 * @package Modules\Asset\Domain\RepositoryInterfaces
 */
interface AssetRepositoryInterface
{
    /**
     * Create a new asset
     *
     * @param Asset $asset
     * @return void
     */
    public function create(Asset $asset): void;

    /**
     * Find asset by ID
     *
     * @param string $id
     * @return Asset|null
     */
    public function findById(string $id): ?Asset;

    /**
     * Find asset by serial number
     *
     * @param string $serialNumber
     * @return Asset|null
     */
    public function findBySerialNumber(string $serialNumber): ?Asset;

    /**
     * Get all assets for a tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array ['data' => Asset[], 'total' => int, 'page' => int, 'limit' => int]
     */
    public function getAllByTenant(
        string $tenantId,
        array $filters = [],
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Find all assets by owner
     *
     * @param string $tenantId
     * @param string $assetOwnerId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAllByOwner(
        string $tenantId,
        string $assetOwnerId,
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Find all assets by status
     *
     * @param string $tenantId
     * @param string $status
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAllByStatus(
        string $tenantId,
        string $status,
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Update an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function update(Asset $asset): void;

    /**
     * Delete an asset (soft delete)
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Get total count by tenant
     *
     * @param string $tenantId
     * @return int
     */
    public function countByTenant(string $tenantId): int;

    /**
     * Get count by status
     *
     * @param string $tenantId
     * @param string $status
     * @return int
     */
    public function countByStatus(string $tenantId, string $status): int;
}
