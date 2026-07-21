<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

final class VehicleRentalChartExceptionReportService
{
    private const MAX_REPORT_DAYS = 366;

    public function __construct(private readonly VehicleRentalReportValueFormatter $values) {}

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(array $params, ReportDefinition $definition): array
    {
        $rows = collect($this->rows($params));
        $summary = [
            'exceptions' => $rows->count(),
            'missing_charts' => $rows->where('exception_type', 'missing_chart')->count(),
            'duplicate_assignment_dates' => $rows->where('exception_type', 'duplicate_assignment_date')->count(),
            'duplicate_vehicle_dates' => $rows->where('exception_type', 'duplicate_vehicle_date')->count(),
        ];
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));
        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'data' => $items,
            'summary' => $summary,
            'meta' => [
                'current_page' => $page,
                'from' => $items->isEmpty() ? null : (($page - 1) * $perPage) + 1,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'to' => $items->isEmpty() ? null : min($page * $perPage, $total),
                'total' => $total,
            ],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'report' => $definition->toArray(),
        ];
    }

    /** @param array<string, mixed> $params @return Collection<int, array<string, mixed>> */
    public function exportRows(array $params): Collection
    {
        $rows = collect($this->rows($params));
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ($rows->count() > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$rows->count()} rows. Narrow the filters to {$limit} rows or fewer before exporting."],
            ]);
        }

        return $rows;
    }

    /** @param array<string, mixed> $params @return list<array<string, mixed>> */
    private function rows(array $params): array
    {
        [$dateFrom, $dateTo] = $this->period($params);
        if ($dateTo->lessThan($dateFrom)) return [];

        $assignmentsQuery = $this->assignmentQuery($params, $dateFrom, $dateTo);
        $assignments = $assignmentsQuery->get();
        if ($assignments->isEmpty()) return [];

        $charts = DB::table('vehicle_rental_running_charts as charts')
            ->whereIn('charts.assignment_id', $assignments->pluck('id')->all())
            ->where('charts.active_marker', true)
            ->where('charts.status', '!=', RentalRunningChartStatus::Reversed->value)
            ->whereBetween('charts.operational_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->select(['charts.id', 'charts.assignment_id', 'charts.operational_date', 'charts.chart_number'])
            ->get();
        $chartsByAssignmentDate = $charts->groupBy(
            fn (object $chart): string => (int) $chart->assignment_id.'|'.CarbonImmutable::parse($chart->operational_date)->toDateString(),
        );
        $assignmentsById = $assignments->keyBy('id');
        $rows = [];

        foreach ($assignments as $assignment) {
            [$start, $end] = $this->assignmentPeriod($assignment, $dateFrom, $dateTo);
            if ($end->lessThan($start)) continue;

            for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
                $key = (int) $assignment->id.'|'.$date->toDateString();
                $dayCharts = $chartsByAssignmentDate->get($key, collect());
                if ($dayCharts->isEmpty()) {
                    $rows[] = $this->row(
                        'missing_chart',
                        $date->toDateString(),
                        $assignment,
                        0,
                        [],
                        'The active customer-use assignment has no current Running Chart for this operational date.',
                    );
                } elseif ($dayCharts->count() > 1) {
                    $rows[] = $this->row(
                        'duplicate_assignment_date',
                        $date->toDateString(),
                        $assignment,
                        $dayCharts->count(),
                        $dayCharts->pluck('chart_number')->filter()->values()->all(),
                        'More than one current Running Chart exists for the same assignment and operational date.',
                    );
                }
            }
        }

        foreach ($this->duplicateVehicleDates($charts, $assignmentsById) as [$date, $assignment, $vehicleCharts]) {
            $rows[] = $this->row(
                'duplicate_vehicle_date',
                $date,
                $assignment,
                $vehicleCharts->count(),
                $vehicleCharts->pluck('chart_number')->filter()->values()->all(),
                'The same physical vehicle has current Running Charts under multiple customer-use assignments on this date.',
                'Multiple assignments',
            );
        }

        return $this->filterAndSort(collect($rows), $params)->values()->all();
    }

    /** @param array<string, mixed> $params */
    private function assignmentQuery(array $params, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): Builder
    {
        $query = DB::table('vehicle_rental_assignments as assignments')
            ->join('vehicle_rental_agreements as customer_agreements', 'customer_agreements.id', '=', 'assignments.agreement_id')
            ->leftJoin('customers', 'customers.id', '=', 'customer_agreements.customer_id')
            ->leftJoin('vehicle_rental_assignments as source_assignments', 'source_assignments.id', '=', 'assignments.source_assignment_id')
            ->leftJoin('vehicle_rental_agreements as owner_agreements', 'owner_agreements.id', '=', 'source_assignments.agreement_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'owner_agreements.supplier_id')
            ->join('vehicles', 'vehicles.id', '=', 'assignments.vehicle_id')
            ->where('assignments.side', RentalAssignmentSide::CustomerUse->value)
            ->where('assignments.status', '!=', RentalAssignmentStatus::Cancelled->value)
            ->whereDate('assignments.starts_at', '<=', $dateTo->toDateString())
            ->where(fn (Builder $period): Builder => $period
                ->whereNull('assignments.ends_at')
                ->orWhereDate('assignments.ends_at', '>=', $dateFrom->toDateString()))
            ->select([
                'assignments.id', 'assignments.vehicle_id', 'assignments.starts_at', 'assignments.ends_at',
                'customer_agreements.id as customer_agreement_id', 'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code', 'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id', 'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code', 'suppliers.name as supplier_name',
                'vehicles.vehicle_number', 'vehicles.registration_number',
            ]);

        $query->where('assignments.tenant_id', (int) $params['tenant_id']);
        ($params['organization_unit_id'] ?? null) === null
            ? $query->whereNull('assignments.organization_unit_id')
            : $query->where('assignments.organization_unit_id', (int) $params['organization_unit_id']);
        if (! empty($params['customer_id'])) $query->where('customer_agreements.customer_id', (int) $params['customer_id']);
        if (! empty($params['supplier_id'])) $query->where('owner_agreements.supplier_id', (int) $params['supplier_id']);
        if (! empty($params['vehicle_id'])) $query->where('assignments.vehicle_id', (int) $params['vehicle_id']);
        if (! empty($params['agreement_id'])) {
            $id = (int) $params['agreement_id'];
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('customer_agreements.id', $id)
                ->orWhere('owner_agreements.id', $id));
        }

        return $query;
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function period(array $params): array
    {
        if (empty($params['date_from']) || empty($params['date_to'])) {
            throw ValidationException::withMessages([
                'date_from' => ['The missing and duplicate Running Chart report requires a start and end date.'],
                'date_to' => ['The missing and duplicate Running Chart report requires a start and end date.'],
            ]);
        }
        $from = CarbonImmutable::parse((string) $params['date_from'])->startOfDay();
        $requestedTo = CarbonImmutable::parse((string) $params['date_to'])->startOfDay();
        if ($from->diffInDays($requestedTo) + 1 > self::MAX_REPORT_DAYS) {
            throw ValidationException::withMessages([
                'date_to' => ['The Running Chart exception report cannot exceed '.self::MAX_REPORT_DAYS.' calendar days.'],
            ]);
        }

        return [$from, $requestedTo->min(CarbonImmutable::today())];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function assignmentPeriod(object $assignment, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $start = CarbonImmutable::parse($assignment->starts_at)->startOfDay()->max($dateFrom);
        $end = $assignment->ends_at === null
            ? $dateTo
            : CarbonImmutable::parse($assignment->ends_at)->startOfDay()->min($dateTo);

        return [$start, $end];
    }

    /** @return list<array{string, object, Collection}> */
    private function duplicateVehicleDates(Collection $charts, Collection $assignmentsById): array
    {
        $duplicates = [];
        $groups = $charts->groupBy(function (object $chart) use ($assignmentsById): string {
            $assignment = $assignmentsById->get($chart->assignment_id);
            return (int) ($assignment->vehicle_id ?? 0).'|'.CarbonImmutable::parse($chart->operational_date)->toDateString();
        });
        foreach ($groups as $vehicleDate => $vehicleCharts) {
            if ($vehicleCharts->pluck('assignment_id')->unique()->count() < 2) continue;
            [$vehicleId, $date] = explode('|', (string) $vehicleDate, 2);
            $assignment = $assignmentsById->firstWhere('vehicle_id', (int) $vehicleId);
            if ($assignment !== null) $duplicates[] = [$date, $assignment, $vehicleCharts];
        }

        return $duplicates;
    }

    /** @return array<string, mixed> */
    private function row(
        string $type,
        string $date,
        object $assignment,
        int $chartCount,
        array $chartNumbers,
        string $explanation,
        ?string $customer = null,
    ): array {
        return [
            'operational_date' => $date,
            'exception_type' => $type,
            'vehicle' => $this->values->vehicle($assignment->registration_number ?? null, $assignment->vehicle_number ?? null),
            'assignment' => 'Assignment #'.(int) $assignment->id,
            'customer' => $customer ?? $this->values->party($assignment->customer_code ?? null, $assignment->customer_name ?? null),
            'customer_agreement' => $assignment->customer_agreement_number,
            'owner' => $this->values->party($assignment->supplier_code ?? null, $assignment->supplier_name ?? null),
            'owner_agreement' => $assignment->owner_agreement_number,
            'chart_count' => $chartCount,
            'chart_numbers' => implode(', ', $chartNumbers),
            'explanation' => $explanation,
        ];
    }

    private function filterAndSort(Collection $rows, array $params): Collection
    {
        if (! empty($params['exception_type'])) $rows = $rows->where('exception_type', (string) $params['exception_type']);
        $term = trim((string) ($params['search'] ?? ''));
        if ($term !== '') {
            $needle = mb_strtolower($term);
            $rows = $rows->filter(static fn (array $row): bool => str_contains(
                mb_strtolower(implode(' ', array_map('strval', $row))),
                $needle,
            ));
        }
        $allowed = ['operational_date', 'exception_type', 'vehicle', 'customer', 'customer_agreement', 'chart_count'];
        $sort = (string) ($params['sort'] ?? 'operational_date');
        $sort = in_array($sort, $allowed, true) ? $sort : 'operational_date';

        return (string) ($params['direction'] ?? 'asc') === 'desc'
            ? $rows->sortByDesc($sort, SORT_NATURAL | SORT_FLAG_CASE)
            : $rows->sortBy($sort, SORT_NATURAL | SORT_FLAG_CASE);
    }
}
