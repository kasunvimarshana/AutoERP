<?php

declare(strict_types=1);

namespace Modules\Customer\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Models\VehicleServiceRecord;

/**
 * Vehicle Service Record Repository
 *
 * Handles data access for VehicleServiceRecord model
 * Supports cross-branch service history queries
 */
class VehicleServiceRecordRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new VehicleServiceRecord;
    }

    /**
     * Find service record by service number
     */
    public function findByServiceNumber(string $serviceNumber): ?VehicleServiceRecord
    {
        /** @var VehicleServiceRecord|null */
        return $this->findOneBy(['service_number' => $serviceNumber]);
    }

    /**
     * Check if service number exists
     */
    public function serviceNumberExists(string $serviceNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('service_number', $serviceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get service records for a vehicle
     */
    public function getByVehicle(int $vehicleId): Collection
    {
        return $this->model->newQuery()
            ->where('vehicle_id', $vehicleId)
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records for a customer across all vehicles
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->with(['vehicle'])
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records by branch
     */
    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->with(['vehicle', 'customer'])
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records by service type
     */
    public function getByServiceType(string $serviceType): Collection
    {
        return $this->model->newQuery()
            ->where('service_type', $serviceType)
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get completed service records
     */
    public function getCompleted(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'completed')
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get cross-branch service history for a vehicle
     */
    public function getCrossBranchHistory(int $vehicleId): Collection
    {
        return $this->model->newQuery()
            ->where('vehicle_id', $vehicleId)
            ->whereNotNull('branch_id')
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records within date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('service_date', [$startDate, $endDate])
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get service records with relations
     */
    public function findWithRelations(int $id): ?VehicleServiceRecord
    {
        /** @var VehicleServiceRecord|null */
        return $this->model->newQuery()
            ->with(['vehicle', 'customer'])
            ->find($id);
    }

    /**
     * Get latest service record for a vehicle
     */
    public function getLatestForVehicle(int $vehicleId): ?VehicleServiceRecord
    {
        /** @var VehicleServiceRecord|null */
        return $this->model->newQuery()
            ->where('vehicle_id', $vehicleId)
            ->where('status', 'completed')
            ->orderBy('service_date', 'desc')
            ->first();
    }

    /**
     * Get pending service records
     */
    public function getPending(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'pending')
            ->orderBy('service_date', 'asc')
            ->get();
    }

    /**
     * Get in-progress service records
     */
    public function getInProgress(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'in_progress')
            ->orderBy('service_date', 'asc')
            ->get();
    }

    /**
     * Get service statistics for a vehicle
     */
    public function getVehicleStatistics(int $vehicleId): array
    {
        $records = $this->getByVehicle($vehicleId);

        return [
            'total_services' => $records->count(),
            'completed_services' => $records->where('status', 'completed')->count(),
            'total_cost' => $records->where('status', 'completed')->sum('total_cost'),
            'average_cost' => $records->where('status', 'completed')->avg('total_cost'),
            'last_service_date' => $records->where('status', 'completed')->first()?->service_date?->format('Y-m-d'),
            'service_types' => $records->groupBy('service_type')->map->count()->toArray(),
        ];
    }

    /**
     * Get service statistics for a customer
     */
    public function getCustomerStatistics(int $customerId): array
    {
        $records = $this->getByCustomer($customerId);

        return [
            'total_services' => $records->count(),
            'completed_services' => $records->where('status', 'completed')->count(),
            'total_cost' => $records->where('status', 'completed')->sum('total_cost'),
            'average_cost' => $records->where('status', 'completed')->avg('total_cost'),
            'last_service_date' => $records->where('status', 'completed')->first()?->service_date?->format('Y-m-d'),
            'vehicles_serviced' => $records->unique('vehicle_id')->count(),
            'branches_used' => $records->whereNotNull('branch_id')->unique('branch_id')->count(),
        ];
    }

    /**
     * Search service records
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('service_number', 'like', "%{$query}%")
                    ->orWhere('service_description', 'like', "%{$query}%")
                    ->orWhere('technician_name', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%");
            })
            ->with(['vehicle', 'customer'])
            ->orderBy('service_date', 'desc')
            ->get();
    }
}
