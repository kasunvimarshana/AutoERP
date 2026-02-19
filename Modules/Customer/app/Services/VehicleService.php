<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Models\Vehicle;
use Modules\Customer\Repositories\VehicleRepository;

/**
 * Vehicle Service
 *
 * Contains business logic for Vehicle operations
 * Extends BaseService for common service layer functionality
 */
class VehicleService extends BaseService
{
    /**
     * VehicleService constructor
     */
    public function __construct(
        private readonly VehicleRepository $vehicleRepository,
        private readonly CustomerService $customerService
    ) {
        parent::__construct($vehicleRepository);
    }

    /**
     * Create a new vehicle
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate customer exists
        $this->customerService->getById($data['customer_id']);

        // Validate registration number uniqueness
        if ($this->vehicleRepository->registrationNumberExists($data['registration_number'])) {
            throw ValidationException::withMessages([
                'registration_number' => ['The registration number has already been taken.'],
            ]);
        }

        // Validate VIN uniqueness if provided
        if (isset($data['vin']) && $this->vehicleRepository->vinExists($data['vin'])) {
            throw ValidationException::withMessages([
                'vin' => ['The VIN has already been taken.'],
            ]);
        }

        // Generate unique vehicle number if not provided
        if (! isset($data['vehicle_number'])) {
            $data['vehicle_number'] = $this->generateUniqueVehicleNumber();
        }

        return parent::create($data);
    }

    /**
     * Update vehicle
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate registration number uniqueness if provided
        if (isset($data['registration_number']) &&
            $this->vehicleRepository->registrationNumberExists($data['registration_number'], $id)) {
            throw ValidationException::withMessages([
                'registration_number' => ['The registration number has already been taken.'],
            ]);
        }

        // Validate VIN uniqueness if provided
        if (isset($data['vin']) && $this->vehicleRepository->vinExists($data['vin'], $id)) {
            throw ValidationException::withMessages([
                'vin' => ['The VIN has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get vehicle with customer and service records
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->vehicleRepository->findWithRelations($id);
    }

    /**
     * Get vehicles by customer
     */
    public function getByCustomer(int $customerId): mixed
    {
        return $this->vehicleRepository->getByCustomer($customerId);
    }

    /**
     * Search vehicles
     */
    public function search(string $query): mixed
    {
        return $this->vehicleRepository->search($query);
    }

    /**
     * Get vehicles due for service
     */
    public function getDueForService(): mixed
    {
        return $this->vehicleRepository->getDueForService();
    }

    /**
     * Get vehicles with expiring insurance
     */
    public function getWithExpiringInsurance(int $daysThreshold = 30): mixed
    {
        return $this->vehicleRepository->getWithExpiringInsurance($daysThreshold);
    }

    /**
     * Update vehicle mileage
     */
    public function updateMileage(int $id, int $mileage): mixed
    {
        $vehicle = $this->vehicleRepository->findOrFail($id);

        if ($mileage < $vehicle->current_mileage) {
            throw ValidationException::withMessages([
                'mileage' => ['New mileage cannot be less than current mileage.'],
            ]);
        }

        return $this->vehicleRepository->updateMileage($id, $mileage);
    }

    /**
     * Update vehicle service information after service
     */
    public function updateAfterService(int $id, array $serviceData): mixed
    {
        $vehicle = $this->vehicleRepository->findOrFail($id);

        $updateData = [
            'last_service_date' => $serviceData['service_date'] ?? now(),
            'current_mileage' => $serviceData['mileage'] ?? $vehicle->current_mileage,
        ];

        if (isset($serviceData['next_service_mileage'])) {
            $updateData['next_service_mileage'] = $serviceData['next_service_mileage'];
        }

        if (isset($serviceData['next_service_date'])) {
            $updateData['next_service_date'] = $serviceData['next_service_date'];
        }

        $vehicle = $this->update($id, $updateData);

        // Update customer's last service date
        $this->customerService->updateLastServiceDate(
            $vehicle->customer_id,
            $updateData['last_service_date']
        );

        return $vehicle;
    }

    /**
     * Transfer vehicle ownership to another customer
     */
    public function transferOwnership(int $vehicleId, int $newCustomerId, string $notes = ''): mixed
    {
        // Validate new customer exists
        $this->customerService->getById($newCustomerId);

        $vehicle = $this->vehicleRepository->findOrFail($vehicleId);
        $oldCustomerId = $vehicle->customer_id;

        // Update vehicle owner
        return $this->update($vehicleId, [
            'customer_id' => $newCustomerId,
            'notes' => $notes ? "{$vehicle->notes}\n\nOwnership transferred from customer ID {$oldCustomerId}. {$notes}" : $vehicle->notes,
        ]);
    }

    /**
     * Change vehicle status
     */
    public function changeStatus(int $id, string $status): mixed
    {
        if (! in_array($status, ['active', 'inactive', 'sold', 'scrapped'])) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status value.'],
            ]);
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Generate unique vehicle number
     */
    protected function generateUniqueVehicleNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $vehicleNumber = Vehicle::generateVehicleNumber();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique vehicle number after maximum attempts');
            }
        } while ($this->vehicleRepository->vehicleNumberExists($vehicleNumber));

        return $vehicleNumber;
    }

    /**
     * Get vehicle service history statistics
     */
    public function getServiceStatistics(int $vehicleId): array
    {
        $vehicle = $this->vehicleRepository->findWithRelations($vehicleId);

        if (! $vehicle) {
            throw new ServiceException('Vehicle not found');
        }

        $serviceRecords = $vehicle->serviceRecords;

        return [
            'total_services' => $serviceRecords->count(),
            'total_cost' => $serviceRecords->sum('total_cost'),
            'total_labor_cost' => $serviceRecords->sum('labor_cost'),
            'total_parts_cost' => $serviceRecords->sum('parts_cost'),
            'last_service_date' => $vehicle->last_service_date?->format('Y-m-d'),
            'next_service_date' => $vehicle->next_service_date?->format('Y-m-d'),
            'is_due_for_service' => $vehicle->isDueForServiceByMileage() || $vehicle->isDueForServiceByDate(),
            'current_mileage' => $vehicle->current_mileage,
            'next_service_mileage' => $vehicle->next_service_mileage,
        ];
    }
}
