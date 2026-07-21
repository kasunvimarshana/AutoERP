<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

final class VehicleRentalOperationalReportService
{
    public function __construct(
        private readonly OperationalReportResponseBuilder $responses,
        private readonly VehicleRentalReportValueFormatter $values,
    ) {}

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(string $key, array $params, ReportDefinition $definition): array
    {
        $query = $this->query($key, $params);
        $summary = $this->summary($key, clone $query);
        $this->sort($key, $query, $params);

        return $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($key, $row),
            $definition,
            $summary,
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
    }

    /** @param array<string, mixed> $params @return Collection<int, array<string, mixed>> */
    public function exportRows(string $key, array $params): Collection
    {
        $query = $this->query($key, $params);
        $this->sort($key, $query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($key, $row));
    }

    /** @param array<string, mixed> $params */
    private function query(string $key, array $params): Builder
    {
        return match ($key) {
            VehicleRentalReportService::RUNNING_CHART => $this->runningChartQuery($params),
            VehicleRentalReportService::RENTAL_HISTORY => $this->rentalHistoryQuery($params),
            default => throw new InvalidArgumentException("Operational Vehicle Rental report [{$key}] is not defined."),
        };
    }

    /** @param array<string, mixed> $params */
    private function runningChartQuery(array $params): Builder
    {
        $query = DB::table('vehicle_rental_running_charts as charts')
            ->join('vehicle_rental_assignments as assignments', 'assignments.id', '=', 'charts.assignment_id')
            ->join('vehicle_rental_agreements as customer_agreements', 'customer_agreements.id', '=', 'assignments.agreement_id')
            ->leftJoin('customers', 'customers.id', '=', 'customer_agreements.customer_id')
            ->leftJoin('vehicle_rental_assignments as source_assignments', 'source_assignments.id', '=', 'assignments.source_assignment_id')
            ->leftJoin('vehicle_rental_agreements as owner_agreements', 'owner_agreements.id', '=', 'source_assignments.agreement_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'owner_agreements.supplier_id')
            ->join('vehicles', 'vehicles.id', '=', 'assignments.vehicle_id')
            ->leftJoin('hr_employees as drivers', 'drivers.id', '=', 'charts.driver_employee_id')
            ->leftJoin('vehicle_rental_running_charts as replaced_charts', 'replaced_charts.id', '=', 'charts.replaces_running_chart_id')
            ->where('assignments.side', RentalAssignmentSide::CustomerUse->value)
            ->select([
                'charts.id', 'charts.chart_number', 'charts.operational_date', 'charts.starts_at', 'charts.ends_at',
                'charts.start_odometer', 'charts.end_odometer', 'charts.total_km', 'charts.garage_km', 'charts.commercial_km',
                'charts.normal_overtime_hours', 'charts.double_overtime_hours', 'charts.triple_overtime_hours',
                'charts.night_out_count', 'charts.trip_origin', 'charts.trip_destination', 'charts.purpose', 'charts.remarks', 'charts.status',
                'customer_agreements.id as customer_agreement_id', 'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code', 'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id', 'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code', 'suppliers.name as supplier_name',
                'vehicles.id as vehicle_id', 'vehicles.vehicle_number', 'vehicles.registration_number',
                'drivers.id as driver_id', 'drivers.employee_number', 'drivers.display_name as driver_name',
                'replaced_charts.chart_number as replaced_chart_number',
            ]);

        $this->contextScope($query, 'charts', $params);
        $this->dateRange($query, 'charts.operational_date', $params);
        $this->commonFilters($query, $params, 'assignments', 'customer_agreements', 'owner_agreements');
        if (! empty($params['chart_status'])) {
            $query->where('charts.status', (string) $params['chart_status']);
        }
        if (! empty($params['driver_employee_id'])) {
            $query->where('charts.driver_employee_id', (int) $params['driver_employee_id']);
        }
        $this->search($query, $params, [
            'charts.chart_number', 'customer_agreements.agreement_number', 'owner_agreements.agreement_number',
            'customers.code', 'customers.name', 'suppliers.code', 'suppliers.name',
            'vehicles.vehicle_number', 'vehicles.registration_number', 'drivers.employee_number', 'drivers.display_name',
        ]);

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function rentalHistoryQuery(array $params): Builder
    {
        $chartTotals = DB::table('vehicle_rental_running_charts as history_charts')
            ->where('history_charts.status', RentalRunningChartStatus::Finalized->value)
            ->where('history_charts.active_marker', true)
            ->selectRaw(
                'history_charts.assignment_id, COUNT(*) as chart_count, '
                .'COALESCE(SUM(history_charts.total_km), 0) as total_km, '
                .'COALESCE(SUM(history_charts.garage_km), 0) as garage_km, '
                .'COALESCE(SUM(history_charts.commercial_km), 0) as commercial_km'
            )
            ->groupBy('history_charts.assignment_id');

        $query = DB::table('vehicle_rental_assignments as assignments')
            ->join('vehicle_rental_agreements as customer_agreements', 'customer_agreements.id', '=', 'assignments.agreement_id')
            ->leftJoin('customers', 'customers.id', '=', 'customer_agreements.customer_id')
            ->leftJoin('vehicle_rental_assignments as source_assignments', 'source_assignments.id', '=', 'assignments.source_assignment_id')
            ->leftJoin('vehicle_rental_agreements as owner_agreements', 'owner_agreements.id', '=', 'source_assignments.agreement_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'owner_agreements.supplier_id')
            ->join('vehicles', 'vehicles.id', '=', 'assignments.vehicle_id')
            ->leftJoin('hr_employees as drivers', 'drivers.id', '=', 'assignments.driver_employee_id')
            ->leftJoin('vehicle_rental_assignments as replaced_assignments', 'replaced_assignments.id', '=', 'assignments.replaces_assignment_id')
            ->leftJoin('vehicles as replaced_vehicles', 'replaced_vehicles.id', '=', 'replaced_assignments.vehicle_id')
            ->leftJoinSub($chartTotals, 'chart_totals', 'chart_totals.assignment_id', '=', 'assignments.id')
            ->where('assignments.side', RentalAssignmentSide::CustomerUse->value)
            ->select([
                'assignments.id', 'assignments.status', 'assignments.starts_at', 'assignments.ends_at',
                'assignments.handover_odometer', 'assignments.return_odometer', 'assignments.self_drive', 'assignments.replacement_reason',
                'customer_agreements.id as customer_agreement_id', 'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code', 'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id', 'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code', 'suppliers.name as supplier_name',
                'vehicles.id as vehicle_id', 'vehicles.vehicle_number', 'vehicles.registration_number',
                'drivers.id as driver_id', 'drivers.employee_number', 'drivers.display_name as driver_name',
                'replaced_vehicles.vehicle_number as replaced_vehicle_number', 'replaced_vehicles.registration_number as replaced_registration_number',
                DB::raw('COALESCE(chart_totals.chart_count, 0) as chart_count'),
                DB::raw('COALESCE(chart_totals.total_km, 0) as total_km'),
                DB::raw('COALESCE(chart_totals.garage_km, 0) as garage_km'),
                DB::raw('COALESCE(chart_totals.commercial_km, 0) as commercial_km'),
            ]);

        $this->contextScope($query, 'assignments', $params);
        $this->overlapRange($query, 'assignments.starts_at', 'assignments.ends_at', $params);
        $this->commonFilters($query, $params, 'assignments', 'customer_agreements', 'owner_agreements');
        if (! empty($params['assignment_status'])) {
            $query->where('assignments.status', (string) $params['assignment_status']);
        }
        if (! empty($params['driver_employee_id'])) {
            $query->where('assignments.driver_employee_id', (int) $params['driver_employee_id']);
        }
        $this->search($query, $params, [
            'customer_agreements.agreement_number', 'owner_agreements.agreement_number',
            'customers.code', 'customers.name', 'suppliers.code', 'suppliers.name',
            'vehicles.vehicle_number', 'vehicles.registration_number', 'drivers.employee_number', 'drivers.display_name',
            'assignments.replacement_reason',
        ]);

        return $query;
    }

    private function summary(string $key, Builder $query): array
    {
        if ($key === VehicleRentalReportService::RUNNING_CHART) {
            $totals = $query->select([])->selectRaw(
                "COUNT(charts.id) as chart_count, "
                ."SUM(CASE WHEN charts.status = 'finalized' THEN 1 ELSE 0 END) as finalized_count, "
                ."SUM(CASE WHEN charts.status = 'draft' THEN 1 ELSE 0 END) as draft_count, "
                ."SUM(CASE WHEN charts.status = 'reversed' THEN 1 ELSE 0 END) as reversed_count, "
                .'COALESCE(SUM(charts.total_km), 0) as total_km, COALESCE(SUM(charts.garage_km), 0) as garage_km, '
                .'COALESCE(SUM(charts.commercial_km), 0) as commercial_km, '
                .'COALESCE(SUM(charts.normal_overtime_hours), 0) as normal_ot_hours, '
                .'COALESCE(SUM(charts.double_overtime_hours), 0) as double_ot_hours, '
                .'COALESCE(SUM(charts.triple_overtime_hours), 0) as triple_ot_hours, '
                .'COALESCE(SUM(charts.night_out_count), 0) as night_outs'
            )->first();

            return [
                'charts' => (int) ($totals->chart_count ?? 0),
                'finalized' => (int) ($totals->finalized_count ?? 0),
                'draft' => (int) ($totals->draft_count ?? 0),
                'reversed' => (int) ($totals->reversed_count ?? 0),
                'total_km' => $this->values->decimal($totals->total_km ?? 0),
                'garage_km' => $this->values->decimal($totals->garage_km ?? 0),
                'commercial_km' => $this->values->decimal($totals->commercial_km ?? 0),
                'normal_ot_hours' => $this->values->decimal($totals->normal_ot_hours ?? 0),
                'double_ot_hours' => $this->values->decimal($totals->double_ot_hours ?? 0),
                'triple_ot_hours' => $this->values->decimal($totals->triple_ot_hours ?? 0),
                'night_outs' => (int) ($totals->night_outs ?? 0),
            ];
        }

        $totals = $query->select([])->selectRaw(
            "COUNT(assignments.id) as assignment_count, "
            ."SUM(CASE WHEN assignments.status = 'active' THEN 1 ELSE 0 END) as active_count, "
            ."SUM(CASE WHEN assignments.status = 'returned' THEN 1 ELSE 0 END) as returned_count, "
            ."SUM(CASE WHEN assignments.status = 'replaced' THEN 1 ELSE 0 END) as replaced_count, "
            .'COALESCE(SUM(chart_totals.chart_count), 0) as chart_count, '
            .'COALESCE(SUM(chart_totals.total_km), 0) as total_km, '
            .'COALESCE(SUM(chart_totals.commercial_km), 0) as commercial_km'
        )->first();

        return [
            'assignments' => (int) ($totals->assignment_count ?? 0),
            'active' => (int) ($totals->active_count ?? 0),
            'returned' => (int) ($totals->returned_count ?? 0),
            'replaced' => (int) ($totals->replaced_count ?? 0),
            'running_charts' => (int) ($totals->chart_count ?? 0),
            'total_km' => $this->values->decimal($totals->total_km ?? 0),
            'commercial_km' => $this->values->decimal($totals->commercial_km ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function row(string $key, object $row): array
    {
        return $key === VehicleRentalReportService::RUNNING_CHART
            ? $this->runningChartRow($row)
            : $this->rentalHistoryRow($row);
    }

    /** @return array<string, mixed> */
    private function runningChartRow(object $row): array
    {
        return [
            'operational_date' => $this->values->date($row->operational_date),
            'chart_number' => $row->chart_number,
            'status' => $row->status,
            'customer' => $this->values->party($row->customer_code, $row->customer_name),
            'customer_agreement' => $row->customer_agreement_number,
            'owner' => $this->values->party($row->supplier_code, $row->supplier_name),
            'owner_agreement' => $row->owner_agreement_number,
            'vehicle' => $this->values->vehicle($row->registration_number, $row->vehicle_number),
            'driver' => $this->values->party($row->employee_number, $row->driver_name),
            'starts_at' => $this->values->dateTime($row->starts_at),
            'ends_at' => $this->values->dateTime($row->ends_at),
            'start_odometer' => $this->values->nullableDecimal($row->start_odometer),
            'end_odometer' => $this->values->nullableDecimal($row->end_odometer),
            'total_km' => $this->values->decimal($row->total_km),
            'garage_km' => $this->values->decimal($row->garage_km),
            'commercial_km' => $this->values->decimal($row->commercial_km),
            'normal_ot_hours' => $this->values->decimal($row->normal_overtime_hours),
            'double_ot_hours' => $this->values->decimal($row->double_overtime_hours),
            'triple_ot_hours' => $this->values->decimal($row->triple_overtime_hours),
            'night_outs' => (int) $row->night_out_count,
            'origin' => $row->trip_origin,
            'destination' => $row->trip_destination,
            'purpose' => $row->purpose,
            'replaces_chart' => $row->replaced_chart_number,
            'remarks' => $row->remarks,
        ];
    }

    /** @return array<string, mixed> */
    private function rentalHistoryRow(object $row): array
    {
        return [
            'assignment' => 'Assignment #'.(int) $row->id,
            'starts_at' => $this->values->dateTime($row->starts_at),
            'ends_at' => $this->values->dateTime($row->ends_at),
            'status' => $row->status,
            'customer' => $this->values->party($row->customer_code, $row->customer_name),
            'customer_agreement' => $row->customer_agreement_number,
            'owner' => $this->values->party($row->supplier_code, $row->supplier_name),
            'owner_agreement' => $row->owner_agreement_number,
            'vehicle' => $this->values->vehicle($row->registration_number, $row->vehicle_number),
            'driver_mode' => (bool) $row->self_drive ? 'Self-drive' : $this->values->party($row->employee_number, $row->driver_name),
            'handover_odometer' => $this->values->nullableDecimal($row->handover_odometer),
            'return_odometer' => $this->values->nullableDecimal($row->return_odometer),
            'replaced_vehicle' => $this->values->vehicle($row->replaced_registration_number, $row->replaced_vehicle_number),
            'replacement_reason' => $row->replacement_reason,
            'running_charts' => (int) $row->chart_count,
            'total_km' => $this->values->decimal($row->total_km),
            'garage_km' => $this->values->decimal($row->garage_km),
            'commercial_km' => $this->values->decimal($row->commercial_km),
        ];
    }

    /** @param array<string, mixed> $params */
    private function sort(string $key, Builder $query, array $params): void
    {
        $maps = $key === VehicleRentalReportService::RUNNING_CHART
            ? [
                'operational_date' => 'charts.operational_date', 'chart_number' => 'charts.chart_number',
                'status' => 'charts.status', 'customer' => 'customers.name', 'vehicle' => 'vehicles.registration_number',
                'commercial_km' => 'charts.commercial_km',
            ]
            : [
                'assignment' => 'assignments.id', 'starts_at' => 'assignments.starts_at', 'ends_at' => 'assignments.ends_at',
                'status' => 'assignments.status', 'customer' => 'customers.name', 'owner' => 'suppliers.name',
                'vehicle' => 'vehicles.registration_number', 'commercial_km' => 'chart_totals.commercial_km',
            ];
        $default = $key === VehicleRentalReportService::RUNNING_CHART ? 'charts.operational_date' : 'assignments.starts_at';
        $column = $maps[(string) ($params['sort'] ?? '')] ?? $default;
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($column, $direction)
            ->orderBy($key === VehicleRentalReportService::RUNNING_CHART ? 'charts.id' : 'assignments.id', $direction);
    }

    /** @param array<string, mixed> $params */
    private function commonFilters(Builder $query, array $params, string $assignments, string $customerAgreements, string $ownerAgreements): void
    {
        if (! empty($params['customer_id'])) $query->where($customerAgreements.'.customer_id', (int) $params['customer_id']);
        if (! empty($params['supplier_id'])) $query->where($ownerAgreements.'.supplier_id', (int) $params['supplier_id']);
        if (! empty($params['vehicle_id'])) $query->where($assignments.'.vehicle_id', (int) $params['vehicle_id']);
        if (! empty($params['agreement_id'])) {
            $id = (int) $params['agreement_id'];
            $query->where(fn (Builder $scope): Builder => $scope
                ->where($customerAgreements.'.id', $id)
                ->orWhere($ownerAgreements.'.id', $id));
        }
    }

    /** @param array<string, mixed> $params */
    private function contextScope(Builder $query, string $alias, array $params): void
    {
        $query->where($alias.'.tenant_id', (int) $params['tenant_id']);
        ($params['organization_unit_id'] ?? null) === null
            ? $query->whereNull($alias.'.organization_unit_id')
            : $query->where($alias.'.organization_unit_id', (int) $params['organization_unit_id']);
    }

    /** @param array<string, mixed> $params */
    private function dateRange(Builder $query, string $column, array $params): void
    {
        if (! empty($params['date_from'])) $query->whereDate($column, '>=', (string) $params['date_from']);
        if (! empty($params['date_to'])) $query->whereDate($column, '<=', (string) $params['date_to']);
    }

    /** @param array<string, mixed> $params */
    private function overlapRange(Builder $query, string $start, string $end, array $params): void
    {
        if (! empty($params['date_to'])) $query->whereDate($start, '<=', (string) $params['date_to']);
        if (! empty($params['date_from'])) {
            $query->where(fn (Builder $period): Builder => $period
                ->whereNull($end)
                ->orWhereDate($end, '>=', (string) $params['date_from']));
        }
    }

    /** @param array<string, mixed> $params @param list<string> $columns */
    private function search(Builder $query, array $params, array $columns): void
    {
        $term = trim((string) ($params['search'] ?? ''));
        if ($term === '') return;
        $query->where(function (Builder $scope) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                $index === 0
                    ? $scope->where($column, 'like', '%'.$term.'%')
                    : $scope->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
