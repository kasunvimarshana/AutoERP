<?php

namespace App\Modules\CustomerManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\CustomerManagement\Events\VehicleOwnershipTransferred;
use App\Modules\CustomerManagement\Models\Vehicle;
use App\Modules\CustomerManagement\Models\VehicleOwnershipHistory;
use App\Modules\CustomerManagement\Repositories\VehicleRepository;
use Illuminate\Support\Facades\DB;

class VehicleService extends BaseService
{
    public function __construct(VehicleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Search vehicles with filters
     */
    public function search(array $criteria)
    {
        return $this->repository->search($criteria);
    }

    /**
     * Find vehicle by VIN
     */
    public function findByVin(string $vin): ?Vehicle
    {
        return $this->repository->findByVin($vin);
    }

    /**
     * Find vehicle by registration
     */
    public function findByRegistration(string $registration): ?Vehicle
    {
        return $this->repository->findByRegistration($registration);
    }

    /**
     * Get vehicles due for service
     */
    public function getServiceDue()
    {
        return $this->repository->getServiceDue();
    }

    /**
     * Update vehicle mileage
     */
    public function updateMileage(int $id, float $mileage): Vehicle
    {
        return $this->repository->updateMileage($id, $mileage);
    }

    /**
     * Transfer vehicle ownership
     */
    public function transferOwnership(int $vehicleId, int $newCustomerId, array $transferData = []): Vehicle
    {
        try {
            DB::beginTransaction();

            $vehicle = $this->repository->findOrFail($vehicleId);
            $previousCustomerId = $vehicle->current_customer_id;

            // Close current ownership history
            VehicleOwnershipHistory::where('vehicle_id', $vehicleId)
                ->whereNull('end_date')
                ->update([
                    'end_date' => now(),
                    'transfer_mileage' => $vehicle->current_mileage,
                    'transfer_reason' => $transferData['reason'] ?? 'sale',
                ]);

            // Create new ownership record
            VehicleOwnershipHistory::create([
                'vehicle_id' => $vehicleId,
                'customer_id' => $newCustomerId,
                'start_date' => now(),
                'purchase_mileage' => $vehicle->current_mileage,
                'notes' => $transferData['notes'] ?? null,
            ]);

            // Update vehicle current owner
            $vehicle->current_customer_id = $newCustomerId;
            $vehicle->ownership_start_date = now();
            $vehicle->save();

            DB::commit();

            // Dispatch ownership transfer event
            event(new VehicleOwnershipTransferred($vehicle, $previousCustomerId, $newCustomerId));

            return $vehicle->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record service completion
     */
    public function recordService(int $vehicleId, float $mileage): Vehicle
    {
        $vehicle = $this->repository->findOrFail($vehicleId);

        $vehicle->last_service_date = now();
        $vehicle->last_service_mileage = $mileage;
        $vehicle->current_mileage = $mileage;

        // Calculate next service
        $vehicle->next_service_date = now()->addDays($vehicle->service_interval_days);
        $vehicle->next_service_mileage = $vehicle->calculateNextServiceMileage();
        $vehicle->total_services += 1;

        $vehicle->save();

        return $vehicle->fresh();
    }
}
