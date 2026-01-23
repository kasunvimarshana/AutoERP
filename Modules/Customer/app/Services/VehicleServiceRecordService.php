<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Models\VehicleServiceRecord;
use Modules\Customer\Repositories\CustomerRepository;
use Modules\Customer\Repositories\VehicleRepository;
use Modules\Customer\Repositories\VehicleServiceRecordRepository;

/**
 * Vehicle Service Record Service
 *
 * Contains business logic for service record operations
 * Handles cross-branch service history and vehicle lifecycle management
 */
class VehicleServiceRecordService extends BaseService
{
    /**
     * VehicleServiceRecordService constructor
     */
    public function __construct(
        VehicleServiceRecordRepository $repository,
        private readonly VehicleRepository $vehicleRepository,
        private readonly CustomerRepository $customerRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new service record
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        // Validate vehicle exists
        $vehicle = $this->vehicleRepository->find($data['vehicle_id']);
        if (! $vehicle) {
            throw new ServiceException('Vehicle not found');
        }

        // Validate customer exists
        $customer = $this->customerRepository->find($data['customer_id']);
        if (! $customer) {
            throw new ServiceException('Customer not found');
        }

        // Verify customer owns the vehicle
        if ($vehicle->customer_id !== $customer->id) {
            throw new ServiceException('Vehicle does not belong to the specified customer');
        }

        // Generate unique service number if not provided
        if (! isset($data['service_number'])) {
            $data['service_number'] = $this->generateUniqueServiceNumber();
        }

        // Calculate total cost if not provided
        if (! isset($data['total_cost'])) {
            $data['total_cost'] = ($data['labor_cost'] ?? 0) + ($data['parts_cost'] ?? 0);
        }

        try {
            DB::beginTransaction();

            $serviceRecord = parent::create($data);

            // Update vehicle mileage if service mileage is higher
            if ($data['mileage_at_service'] > $vehicle->current_mileage) {
                $this->vehicleRepository->update($vehicle->id, [
                    'current_mileage' => $data['mileage_at_service'],
                ]);
            }

            // Update vehicle next service info if provided
            if (isset($data['next_service_mileage']) || isset($data['next_service_date'])) {
                $updateData = [];
                if (isset($data['next_service_mileage'])) {
                    $updateData['next_service_mileage'] = $data['next_service_mileage'];
                }
                if (isset($data['next_service_date'])) {
                    $updateData['next_service_date'] = $data['next_service_date'];
                }
                $this->vehicleRepository->update($vehicle->id, $updateData);
            }

            // Update customer last service date
            $this->customerRepository->update($customer->id, [
                'last_service_date' => $data['service_date'],
            ]);

            DB::commit();

            return $serviceRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update service record
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Recalculate total cost if labor or parts cost changed
        if (isset($data['labor_cost']) || isset($data['parts_cost'])) {
            $record = $this->repository->findOrFail($id);
            $laborCost = $data['labor_cost'] ?? $record->labor_cost;
            $partsCost = $data['parts_cost'] ?? $record->parts_cost;
            $data['total_cost'] = $laborCost + $partsCost;
        }

        return parent::update($id, $data);
    }

    /**
     * Get service records for a vehicle
     */
    public function getByVehicle(int $vehicleId): mixed
    {
        return $this->repository->getByVehicle($vehicleId);
    }

    /**
     * Get service records for a customer across all vehicles
     */
    public function getByCustomer(int $customerId): mixed
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Get service records by branch (cross-branch query)
     */
    public function getByBranch(string $branchId): mixed
    {
        return $this->repository->getByBranch($branchId);
    }

    /**
     * Get cross-branch service history for a vehicle
     */
    public function getCrossBranchHistory(int $vehicleId): mixed
    {
        return $this->repository->getCrossBranchHistory($vehicleId);
    }

    /**
     * Get service records by service type
     */
    public function getByServiceType(string $serviceType): mixed
    {
        return $this->repository->getByServiceType($serviceType);
    }

    /**
     * Get service records by status
     */
    public function getByStatus(string $status): mixed
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get service records within date range
     */
    public function getByDateRange(string $startDate, string $endDate): mixed
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    /**
     * Get service record with relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Get latest service record for a vehicle
     */
    public function getLatestForVehicle(int $vehicleId): mixed
    {
        return $this->repository->getLatestForVehicle($vehicleId);
    }

    /**
     * Get pending service records
     */
    public function getPending(): mixed
    {
        return $this->repository->getPending();
    }

    /**
     * Get in-progress service records
     */
    public function getInProgress(): mixed
    {
        return $this->repository->getInProgress();
    }

    /**
     * Complete a service record
     */
    public function complete(int $id): mixed
    {
        try {
            DB::beginTransaction();

            $serviceRecord = $this->repository->findOrFail($id);

            // Update status to completed
            $serviceRecord = $this->update($id, ['status' => 'completed']);

            // Update vehicle and customer info
            $vehicle = $this->vehicleRepository->find($serviceRecord->vehicle_id);
            if ($vehicle) {
                // Update vehicle mileage
                if ($serviceRecord->mileage_at_service > $vehicle->current_mileage) {
                    $this->vehicleRepository->update($vehicle->id, [
                        'current_mileage' => $serviceRecord->mileage_at_service,
                    ]);
                }

                // Update vehicle next service info
                if ($serviceRecord->next_service_mileage || $serviceRecord->next_service_date) {
                    $updateData = [];
                    if ($serviceRecord->next_service_mileage) {
                        $updateData['next_service_mileage'] = $serviceRecord->next_service_mileage;
                    }
                    if ($serviceRecord->next_service_date) {
                        $updateData['next_service_date'] = $serviceRecord->next_service_date;
                    }
                    $this->vehicleRepository->update($vehicle->id, $updateData);
                }
            }

            // Update customer last service date
            $this->customerRepository->update($serviceRecord->customer_id, [
                'last_service_date' => $serviceRecord->service_date,
            ]);

            DB::commit();

            return $serviceRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a service record
     */
    public function cancel(int $id, ?string $reason = null): mixed
    {
        $data = ['status' => 'cancelled'];
        if ($reason) {
            $data['notes'] = ($data['notes'] ?? '')."\nCancellation Reason: {$reason}";
        }

        return $this->update($id, $data);
    }

    /**
     * Get service statistics for a vehicle
     */
    public function getVehicleStatistics(int $vehicleId): array
    {
        return $this->repository->getVehicleStatistics($vehicleId);
    }

    /**
     * Get service statistics for a customer
     */
    public function getCustomerStatistics(int $customerId): array
    {
        return $this->repository->getCustomerStatistics($customerId);
    }

    /**
     * Search service records
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Generate unique service number
     */
    protected function generateUniqueServiceNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $serviceNumber = VehicleServiceRecord::generateServiceNumber();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique service number after maximum attempts');
            }
        } while ($this->repository->serviceNumberExists($serviceNumber));

        return $serviceNumber;
    }

    /**
     * Get service history summary for a vehicle (cross-branch)
     *
     * Provides a comprehensive summary of all services across all branches
     */
    public function getVehicleServiceHistorySummary(int $vehicleId): array
    {
        $services = $this->repository->getCrossBranchHistory($vehicleId);
        $branches = $services->whereNotNull('branch_id')->unique('branch_id');

        return [
            'total_services' => $services->count(),
            'branches_serviced_at' => $branches->count(),
            'branch_breakdown' => $services->groupBy('branch_id')
                ->map(fn ($items) => [
                    'count' => $items->count(),
                    'total_cost' => $items->sum('total_cost'),
                ])
                ->toArray(),
            'service_types' => $services->groupBy('service_type')
                ->map->count()
                ->toArray(),
            'total_spent' => $services->sum('total_cost'),
            'average_cost' => $services->avg('total_cost'),
            'last_service' => $services->first()?->service_date?->format('Y-m-d'),
        ];
    }
}
