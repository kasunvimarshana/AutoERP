<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceJobQueryService
{
    private const BOARD_PAGE_SIZE = 20;

    private const BOARD_LANES = [
        'pending' => [VehicleServiceJobStatus::Draft->value, VehicleServiceJobStatus::Inspected->value],
        'in_progress' => [VehicleServiceJobStatus::InProgress->value],
        'completed' => [VehicleServiceJobStatus::Completed->value],
        'billing_closed' => [VehicleServiceJobStatus::Invoiced->value, VehicleServiceJobStatus::PartiallyPaid->value, VehicleServiceJobStatus::Paid->value, VehicleServiceJobStatus::Cancelled->value],
    ];

    /** @param array<string, mixed> $filters */
    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->filteredQuery($tenantId, $organizationUnitId, $filters)
            ->latest('job_date')
            ->latest('id')
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $filters @return array<string, array{jobs: Collection, total: int, has_more: bool}> */
    public function board(int $tenantId, ?int $organizationUnitId, array $filters): array
    {
        $base = $this->filteredQuery($tenantId, $organizationUnitId, $filters);
        $lanes = [];
        foreach (self::BOARD_LANES as $lane => $statuses) {
            $query = (clone $base)->whereIn('status', $statuses);
            $total = (clone $query)->count();
            $limit = self::BOARD_PAGE_SIZE * (int) ($filters["{$lane}_page"] ?? 1);
            $lanes[$lane] = [
                'jobs' => $query
                    ->latest('job_date')
                    ->latest('id')
                    ->limit($limit)
                    ->get(),
                'total' => $total,
                'has_more' => $total > $limit,
            ];
        }

        return $lanes;
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(int $tenantId, ?int $organizationUnitId, array $filters): Builder
    {
        $query = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['customer', 'billToCustomer', 'vehicle.make', 'vehicle.model', 'vehicle.currentOwnerships', 'supervisor']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('job_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
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

        return $query;
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
            ->with(['customer', 'vehicle.currentOwnerships']);

        if ($search !== null && trim($search) !== '') {
            $query->where('job_number', 'like', '%'.trim($search).'%');
        }

        return $query->latest('id')->limit($limit)->get();
    }
}
