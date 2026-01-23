<?php

namespace Modules\Vehicle\Services;

use App\Services\Base\BaseService;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Repositories\VehicleRepository;

// use Modules\Customer\Services\CustomerService;
// use Modules\Vehicle\Events\VehicleRegistered;
// use Modules\Vehicle\Events\OwnershipTransferred;
// use Modules\Vehicle\Events\ServiceDue;

/**
 * Vehicle Service
 *
 * Orchestrates business logic for vehicle operations.
 * Demonstrates cross-module coordination with Customer module.
 */
class VehicleService extends BaseService
{
    // Inject other services for cross-module interaction
    // private CustomerService $customerService;

    public function __construct(VehicleRepository $repository)
    {
        parent::__construct($repository);
        // $this->customerService = $customerService;
    }

    /**
     * Register a new vehicle.
     *
     * @throws \Throwable
     */
    public function registerVehicle(array $data): Vehicle
    {
        return $this->transaction(function () use ($data) {
            // Validate unique constraints
            $this->validateUniqueVehicle($data);

            // Verify customer exists (cross-module validation)
            // $customer = $this->customerService->getCustomer($data['customer_id']);

            // Set tenant context
            $data['tenant_id'] = $data['tenant_id'] ?? $this->getCurrentTenantId();

            // Register vehicle
            $vehicle = $this->repository->create($data);

            // Create initial meter reading if provided
            if (isset($data['current_mileage'])) {
                $this->recordMeterReading($vehicle->id, $data['current_mileage']);
            }

            // Log activity
            $this->logActivity('vehicle.registered', [
                'vehicle_id' => $vehicle->id,
                'customer_id' => $vehicle->customer_id,
                'vin' => $vehicle->vin,
            ]);

            // Fire event
            // $this->fireEvent(new VehicleRegistered($vehicle));

            return $vehicle;
        });
    }

    /**
     * Transfer vehicle ownership to another customer.
     * Demonstrates complex cross-module transaction.
     *
     * @throws \Throwable
     */
    public function transferOwnership(int $vehicleId, int $newCustomerId, array $transferData = []): Vehicle
    {
        return $this->transaction(function () use ($vehicleId, $newCustomerId) {
            // Get vehicle and verify it exists
            $vehicle = $this->repository->findOrFail($vehicleId);
            $oldCustomerId = $vehicle->customer_id;

            // Verify new customer exists (cross-module validation)
            // $newCustomer = $this->customerService->getCustomer($newCustomerId);

            // Check authorization
            // $this->authorize('transfer', $vehicle);

            // Record ownership history
            // VehicleOwnership::create([
            //     'vehicle_id' => $vehicleId,
            //     'from_customer_id' => $oldCustomerId,
            //     'to_customer_id' => $newCustomerId,
            //     'transfer_date' => $transferData['transfer_date'] ?? now(),
            //     'notes' => $transferData['notes'] ?? null,
            // ]);

            // Update vehicle owner
            $vehicle = $this->repository->update($vehicleId, [
                'customer_id' => $newCustomerId,
            ]);

            // Log activity
            $this->logActivity('vehicle.ownership_transferred', [
                'vehicle_id' => $vehicleId,
                'from_customer_id' => $oldCustomerId,
                'to_customer_id' => $newCustomerId,
            ]);

            // Fire event
            // $this->fireEvent(new OwnershipTransferred($vehicle, $oldCustomerId, $newCustomerId));

            // Notify both customers
            // $this->notifyOwnershipTransfer($vehicle, $oldCustomerId, $newCustomerId);

            return $vehicle;
        });
    }

    /**
     * Record a meter reading for a vehicle.
     *
     * @throws \Throwable
     */
    public function recordMeterReading(int $vehicleId, int $mileage, array $additionalData = []): void
    {
        $this->transaction(function () use ($vehicleId, $mileage) {
            // Update vehicle mileage
            $this->repository->updateMileage($vehicleId, $mileage);

            // Create meter reading record
            // MeterReading::create([
            //     'vehicle_id' => $vehicleId,
            //     'mileage' => $mileage,
            //     'recorded_at' => $additionalData['recorded_at'] ?? now(),
            //     'recorded_by' => $additionalData['recorded_by'] ?? $this->getCurrentUserId(),
            //     'notes' => $additionalData['notes'] ?? null,
            // ]);

            // Check if service is due
            /** @var Vehicle|null $vehicle */
            $vehicle = $this->repository->find($vehicleId);
            if ($vehicle && $vehicle->needsService()) {
                // $this->fireEvent(new ServiceDue($vehicle));
                // $this->notifyServiceDue($vehicle);
            }

            $this->logActivity('meter_reading.recorded', [
                'vehicle_id' => $vehicleId,
                'mileage' => $mileage,
            ]);
        });
    }

    /**
     * Update vehicle and schedule next service.
     *
     * @throws \Throwable
     */
    public function updateVehicle(int $vehicleId, array $data): Vehicle
    {
        return $this->transaction(function () use ($vehicleId, $data) {
            $vehicle = $this->repository->findOrFail($vehicleId);

            // Validate unique constraints
            $this->validateUniqueVehicle($data, $vehicleId);

            // Update vehicle
            $vehicle = $this->repository->update($vehicleId, $data);

            $this->logActivity('vehicle.updated', [
                'vehicle_id' => $vehicleId,
                'changes' => $data,
            ]);

            return $vehicle;
        });
    }

    /**
     * Get vehicles needing service.
     */
    public function getVehiclesNeedingService(int $perPage = 15)
    {
        return $this->repository->getNeedingService($perPage);
    }

    /**
     * Get complete service history for a vehicle.
     * Demonstrates cross-module data aggregation.
     */
    public function getCompleteServiceHistory(int $vehicleId): array
    {
        $vehicle = $this->repository->findOrFail($vehicleId);

        return [
            'vehicle' => $vehicle,
            'service_records' => [], // Would load from ServiceHistory
            'meter_readings' => [], // Would load from MeterReading
            'job_cards' => [], // Would load from JobCard
            'appointments' => [], // Would load from Appointment
        ];
    }

    /**
     * Validate unique vehicle constraints.
     *
     * @throws \Exception
     */
    protected function validateUniqueVehicle(array $data, ?int $excludeId = null): void
    {
        // Check VIN uniqueness
        if (isset($data['vin'])) {
            $existing = $this->repository->findByVin($data['vin']);
            if ($existing && (! $excludeId || $existing->id !== $excludeId)) {
                throw new \Exception('VIN already exists');
            }
        }

        // Check registration number uniqueness
        if (isset($data['registration_number'])) {
            $existing = $this->repository->findByRegistration($data['registration_number']);
            if ($existing && (! $excludeId || $existing->id !== $excludeId)) {
                throw new \Exception('Registration number already exists');
            }
        }
    }

    /**
     * Notify customer about ownership transfer.
     */
    protected function notifyOwnershipTransfer(Vehicle $vehicle, int $oldCustomerId, int $newCustomerId): void
    {
        // Send notification to old owner
        // $oldCustomer = $this->customerService->getCustomer($oldCustomerId);
        // $this->notify($oldCustomer, new OwnershipTransferredNotification($vehicle, 'old'));

        // Send notification to new owner
        // $newCustomer = $this->customerService->getCustomer($newCustomerId);
        // $this->notify($newCustomer, new OwnershipTransferredNotification($vehicle, 'new'));
    }

    /**
     * Notify customer about service due.
     */
    protected function notifyServiceDue(Vehicle $vehicle): void
    {
        // $customer = $this->customerService->getCustomer($vehicle->customer_id);
        // $this->notify($customer, new ServiceDueNotification($vehicle));
    }
}
