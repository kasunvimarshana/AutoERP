<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\Contracts\AnalyticsServiceInterface;
use Modules\Analytics\Application\DTOs\CreateAnalyticsSnapshotDTO;
use Modules\Analytics\Domain\Entities\AnalyticsSnapshot;
use Modules\Analytics\Domain\Exceptions\AnalyticsSnapshotNotFoundException;
use Modules\Analytics\Domain\RepositoryInterfaces\AnalyticsSnapshotRepositoryInterface;

class AnalyticsService implements AnalyticsServiceInterface
{
    public function __construct(
        private readonly AnalyticsSnapshotRepositoryInterface $repository,
    ) {}

    public function getSnapshotById(int $id, int $tenantId): AnalyticsSnapshot
    {
        $snapshot = $this->repository->findById($id, $tenantId);

        if ($snapshot === null) {
            throw new AnalyticsSnapshotNotFoundException($id);
        }

        return $snapshot;
    }

    public function listSnapshots(int $tenantId, array $filters = []): array
    {
        return $this->repository->findByTenant($tenantId, $filters);
    }

    public function createSnapshot(CreateAnalyticsSnapshotDTO $dto): AnalyticsSnapshot
    {
        return DB::transaction(function () use ($dto): AnalyticsSnapshot {
            $summary = $this->getSummary($dto->tenantId, $dto->orgUnitId);

            $snapshot = new AnalyticsSnapshot(
                id: null,
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                summaryDate: $dto->summaryDate,
                totalRentals: (int) $summary['total_rentals'],
                completedRentals: (int) $summary['completed_rentals'],
                activeRentals: (int) $summary['active_rentals'],
                totalRevenue: (string) $summary['total_revenue'],
                totalServiceCost: (string) $summary['total_service_cost'],
                netRevenue: (string) $summary['net_revenue'],
                metadata: $dto->metadata,
                isActive: true,
                rowVersion: 1,
            );

            return $this->repository->save($snapshot);
        });
    }

    public function getSummary(int $tenantId, ?int $orgUnitId = null): array
    {
        $rentalBase = DB::table('fleet_rentals')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        $serviceBase = DB::table('fleet_service_jobs')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($orgUnitId !== null) {
            $rentalBase->where('org_unit_id', $orgUnitId);
            $serviceBase->where('org_unit_id', $orgUnitId);
        }

        $totalRentals = (int) (clone $rentalBase)->count();
        $completedRentals = (int) (clone $rentalBase)->where('status', 'completed')->count();
        $activeRentals = (int) (clone $rentalBase)->whereIn('status', ['confirmed', 'active'])->count();

        $totalRevenue = $this->decimal((clone $rentalBase)->where('status', 'completed')->sum('total_amount'));
        $totalServiceCost = $this->decimal((clone $serviceBase)->where('status', 'completed')->sum('total_cost'));
        $netRevenue = $this->decimal((float) $totalRevenue - (float) $totalServiceCost);

        return [
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'total_rentals' => $totalRentals,
            'completed_rentals' => $completedRentals,
            'active_rentals' => $activeRentals,
            'total_revenue' => $totalRevenue,
            'total_service_cost' => $totalServiceCost,
            'net_revenue' => $netRevenue,
        ];
    }

    public function deleteSnapshot(int $id, int $tenantId): void
    {
        DB::transaction(function () use ($id, $tenantId): void {
            $this->getSnapshotById($id, $tenantId);
            $this->repository->delete($id, $tenantId);
        });
    }

    private function decimal(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 6, '.', '');
    }
}
