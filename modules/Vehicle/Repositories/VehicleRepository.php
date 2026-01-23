<?php

namespace Modules\Vehicle\Repositories;

use App\Repositories\BaseRepository;
use Modules\Vehicle\Models\Vehicle;

/**
 * Vehicle Repository
 *
 * Handles data access for Vehicle entities.
 */
class VehicleRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function model(): string
    {
        return Vehicle::class;
    }

    /**
     * Find vehicle by VIN.
     */
    public function findByVin(string $vin): ?Vehicle
    {
        return $this->findOneBy(['vin' => $vin]);
    }

    /**
     * Find vehicle by registration number.
     */
    public function findByRegistration(string $registrationNumber): ?Vehicle
    {
        return $this->findOneBy(['registration_number' => $registrationNumber]);
    }

    /**
     * Get vehicles by customer ID.
     */
    public function getByCustomer(int $customerId)
    {
        return $this->findBy(['customer_id' => $customerId]);
    }

    /**
     * Get vehicles needing service.
     */
    public function getNeedingService(int $perPage = 15)
    {
        return $this->model
            ->needingService()
            ->with('customer')
            ->paginate($perPage);
    }

    /**
     * Search vehicles by make, model, or registration.
     */
    public function search(string $searchTerm, int $perPage = 15)
    {
        return $this->model
            ->where(function ($query) use ($searchTerm) {
                $query->where('make', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('model', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('registration_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('vin', 'LIKE', "%{$searchTerm}%");
            })
            ->with('customer')
            ->paginate($perPage);
    }

    /**
     * Get vehicles with complete service history.
     */
    public function getWithServiceHistory(int $perPage = 15)
    {
        return $this->model
            ->with(['serviceHistory', 'meterReadings'])
            ->paginate($perPage);
    }

    /**
     * Update vehicle mileage.
     */
    public function updateMileage(int $vehicleId, int $mileage): Vehicle
    {
        return $this->update($vehicleId, ['current_mileage' => $mileage]);
    }

    /**
     * Get vehicle statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'needing_service' => 0, // Would use needingService scope
            'by_make' => [], // Would group by make
            'average_age' => 0, // Would calculate from year
        ];
    }
}
