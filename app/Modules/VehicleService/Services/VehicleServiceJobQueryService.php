<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceJobQueryService
{
    /** @param array<string, mixed> $filters */
    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        $query = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['customer', 'vehicle.make', 'vehicle.model', 'vehicle.currentCustomerVehicles.customer', 'vehicle.currentSupplierVehicles.supplier', 'supervisor']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('job_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle
                        ->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_number', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'customer_id', 'vehicle_id'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('job_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('job_date', '<=', $filters['date_to']);
        }

        return $query->latest('job_date')->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, VehicleServiceJob> */
    public function lookup(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $search,
        int $limit,
    ): Collection {
        $query = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['customer', 'vehicle.currentCustomerVehicles.customer', 'vehicle.currentSupplierVehicles.supplier']);

        if ($search !== null && trim($search) !== '') {
            $query->where('job_number', 'like', '%'.trim($search).'%');
        }

        return $query->latest('id')->limit($limit)->get();
    }
}
