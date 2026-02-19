<?php

declare(strict_types=1);

namespace Modules\Customer\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Models\Vehicle;

/**
 * Vehicle Repository
 *
 * Handles data access for Vehicle model
 * Extends BaseRepository for common CRUD operations
 */
class VehicleRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Vehicle;
    }

    /**
     * Find vehicle by vehicle number
     */
    public function findByVehicleNumber(string $vehicleNumber): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->findOneBy(['vehicle_number' => $vehicleNumber]);
    }

    /**
     * Find vehicle by registration number
     */
    public function findByRegistrationNumber(string $registrationNumber): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->findOneBy(['registration_number' => $registrationNumber]);
    }

    /**
     * Find vehicle by VIN
     */
    public function findByVin(string $vin): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->findOneBy(['vin' => $vin]);
    }

    /**
     * Check if vehicle number exists
     */
    public function vehicleNumberExists(string $vehicleNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('vehicle_number', $vehicleNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if registration number exists
     */
    public function registrationNumberExists(string $registrationNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('registration_number', $registrationNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if VIN exists
     */
    public function vinExists(string $vin, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('vin', $vin)->whereNotNull('vin');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get vehicles by customer ID
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->get();
    }

    /**
     * Get active vehicles
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->get();
    }

    /**
     * Get vehicles by make
     */
    public function getByMake(string $make): Collection
    {
        return $this->model->newQuery()->where('make', $make)->get();
    }

    /**
     * Get vehicle with customer and service records
     */
    public function findWithRelations(int $id): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->model->newQuery()
            ->with(['customer', 'serviceRecords'])
            ->find($id);
    }

    /**
     * Get vehicles due for service
     */
    public function getDueForService(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereColumn('current_mileage', '>=', 'next_service_mileage')
                    ->orWhere('next_service_date', '<=', now());
            })
            ->with('customer')
            ->get();
    }

    /**
     * Get vehicles with expiring insurance
     */
    public function getWithExpiringInsurance(int $daysThreshold = 30): Collection
    {
        $thresholdDate = now()->addDays($daysThreshold);

        return $this->model->newQuery()
            ->where('status', 'active')
            ->whereNotNull('insurance_expiry')
            ->where('insurance_expiry', '<=', $thresholdDate)
            ->with('customer')
            ->get();
    }

    /**
     * Search vehicles by make, model, registration number, or VIN
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('make', 'like', "%{$query}%")
                    ->orWhere('model', 'like', "%{$query}%")
                    ->orWhere('registration_number', 'like', "%{$query}%")
                    ->orWhere('vin', 'like', "%{$query}%")
                    ->orWhere('vehicle_number', 'like', "%{$query}%");
            })
            ->with('customer')
            ->get();
    }

    /**
     * Update vehicle mileage
     */
    public function updateMileage(int $id, int $mileage): Vehicle
    {
        $vehicle = $this->findOrFail($id);
        $vehicle->current_mileage = $mileage;
        $vehicle->save();

        return $vehicle;
    }
}
