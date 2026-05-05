<?php declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\Vehicle;

/**
 * VehicleRepositoryInterface - Contract for Vehicle persistence
 *
 * @package Modules\Asset\Domain\RepositoryInterfaces
 */
interface VehicleRepositoryInterface
{
    /**
     * Create a new vehicle
     *
     * @param Vehicle $vehicle
     * @return void
     */
    public function create(Vehicle $vehicle): void;

    /**
     * Find vehicle by ID
     *
     * @param string $id
     * @return Vehicle|null
     */
    public function findById(string $id): ?Vehicle;

    /**
     * Find vehicle by VIN
     *
     * @param string $vin
     * @return Vehicle|null
     */
    public function findByVin(string $vin): ?Vehicle;

    /**
     * Find vehicle by registration plate
     *
     * @param string $registrationPlate
     * @return Vehicle|null
     */
    public function findByRegistrationPlate(string $registrationPlate): ?Vehicle;

    /**
     * Get all vehicles for a tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array ['data' => Vehicle[], 'total' => int]
     */
    public function getAllByTenant(
        string $tenantId,
        array $filters = [],
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Find available vehicles for rental
     *
     * @param string $tenantId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAvailableForRental(
        string $tenantId,
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Find vehicles by status
     *
     * @param string $tenantId
     * @param string $status
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getByStatus(
        string $tenantId,
        string $status,
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Find vehicles by type
     *
     * @param string $tenantId
     * @param string $vehicleType
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getByType(
        string $tenantId,
        string $vehicleType,
        int $page = 1,
        int $limit = 50,
    ): array;

    /**
     * Update a vehicle
     *
     * @param Vehicle $vehicle
     * @return void
     */
    public function update(Vehicle $vehicle): void;

    /**
     * Delete a vehicle (soft delete)
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Get count of available vehicles
     *
     * @param string $tenantId
     * @return int
     */
    public function countAvailableForRental(string $tenantId): int;

    /**
     * Check if vehicle with VIN exists
     *
     * @param string $tenantId
     * @param string $vin
     * @return bool
     */
    public function vinExists(string $tenantId, string $vin): bool;

    /**
     * Check if vehicle with registration plate exists
     *
     * @param string $tenantId
     * @param string $registrationPlate
     * @return bool
     */
    public function registrationPlateExists(string $tenantId, string $registrationPlate): bool;
}
