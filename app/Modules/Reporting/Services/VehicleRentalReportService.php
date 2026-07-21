<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalRunningChart;

final class VehicleRentalReportService
{
    public const RUNNING_CHART = 'vehicle-rental/running-chart';
    public const CHART_EXCEPTIONS = 'vehicle-rental/chart-exceptions';
    public const CUSTOMER_INVOICES = 'vehicle-rental/customer-invoices';
    public const OWNER_VOUCHERS = 'vehicle-rental/owner-vouchers';
    public const RENTAL_HISTORY = 'vehicle-rental/rental-history';

    private const MAX_EXCEPTION_REPORT_DAYS = 366;

    /** @var list<string> */
    private const FINANCIAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Reversed->value,
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly OperationalReportResponseBuilder $responses,
    ) {}

    /** @return list<ReportDefinition> */
    public function definitions(): array
    {
        return [
            $this->runningChartDefinition(),
            $this->chartExceptionDefinition(),
            $this->customerInvoiceDefinition(),
            $this->ownerVoucherDefinition(),
            $this->rentalHistoryDefinition(),
        ];
    }

    public function definition(string $key): ReportDefinition
    {
        return match ($key) {
            self::RUNNING_CHART => $this->runningChartDefinition(),
            self::CHART_EXCEPTIONS => $this->chartExceptionDefinition(),
            self::CUSTOMER_INVOICES => $this->customerInvoiceDefinition(),
            self::OWNER_VOUCHERS => $this->ownerVoucherDefinition(),
            self::RENTAL_HISTORY => $this->rentalHistoryDefinition(),
            default => throw new InvalidArgumentException("Vehicle Rental report [{$key}] is not defined."),
        };
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(string $key, array $params): array
    {
        if ($key === self::CHART_EXCEPTIONS) {
            return $this->runChartExceptions($params);
        }

        $query = $this->query($key, $params);
        $summary = $this->summary($key, clone $query);
        $this->sort($key, $query, $params);

        return $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($key, $row),
            $this->definition($key),
            $summary,
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
    }

    /** @param array<string, mixed> $params @return Collection<int, array<string, mixed>> */
    public function exportRows(string $key, array $params): Collection
    {
        if ($key === self::CHART_EXCEPTIONS) {
            $rows = collect($this->chartExceptionRows($params));
            $limit = (int) config('reporting.export_row_limit', 5000);
            if ($rows->count() > $limit) {
                throw ValidationException::withMessages([
                    'filters' => ["The report contains {$rows->count()} rows. Narrow the filters to {$limit} rows or fewer before exporting."],
                ]);
            }

            return $rows;
        }

        $query = $this->query($key, $params);
        $this->sort($key, $query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($key, $row));
    }

    /** @param array<string, mixed> $params */
    private function query(string $key, array $params): Builder
    {
        return match ($key) {
            self::RUNNING_CHART => $this->runningChartQuery($params),
            self::CUSTOMER_INVOICES => $this->financialDocumentQuery($params, RentalCalculationSide::Customer),
            self::OWNER_VOUCHERS => $this->financialDocumentQuery($params, RentalCalculationSide::Owner),
            self::RENTAL_HISTORY => $this->rentalHistoryQuery($params),
            default => throw new InvalidArgumentException("Vehicle Rental report [{$key}] does not use a database query."),
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
                'charts.id',
                'charts.chart_number',
                'charts.operational_date',
                'charts.starts_at',
                'charts.ends_at',
                'charts.start_odometer',
                'charts.end_odometer',
                'charts.total_km',
                'charts.garage_km',
                'charts.commercial_km',
                'charts.normal_overtime_hours',
                'charts.double_overtime_hours',
                'charts.triple_overtime_hours',
                'charts.night_out_count',
                'charts.trip_origin',
                'charts.trip_destination',
                'charts.purpose',
                'charts.remarks',
                'charts.status',
                'charts.finalized_at',
                'customer_agreements.id as customer_agreement_id',
                'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id',
                'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'vehicles.id as vehicle_id',
                'vehicles.vehicle_number',
                'vehicles.registration_number',
                'drivers.id as driver_id',
                'drivers.employee_number',
                'drivers.display_name as driver_name',
                'replaced_charts.chart_number as replaced_chart_number',
            ]);

        $this->contextScope($query, 'charts', $params);
        $this->dateRange($query, 'charts.operational_date', $params);
        $this->commonRentalFilters($query, $params, 'assignments', 'customer_agreements', 'owner_agreements');
        if (! empty($params['chart_status'])) {
            $query->where('charts.status', (string) $params['chart_status']);
        }
        if (! empty($params['driver_employee_id'])) {
            $query->where('charts.driver_employee_id', (int) $params['driver_employee_id']);
        }
        $this->search($query, $params, [
            'charts.chart_number',
            'customer_agreements.agreement_number',
            'owner_agreements.agreement_number',
            'customers.code',
            'customers.name',
            'suppliers.code',
            'suppliers.name',
            'vehicles.vehicle_number',
            'vehicles.registration_number',
            'drivers.employee_number',
            'drivers.display_name',
        ]);

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function financialDocumentQuery(array $params, RentalCalculationSide $side): Builder
    {
        $vehicleTotals = DB::table('vehicle_rental_calculation_sources as calculation_sources')
            ->join('vehicle_rental_running_charts as source_charts', 'source_charts.id', '=', 'calculation_sources.running_chart_id')
            ->join('vehicle_rental_assignments as source_chart_assignments', 'source_chart_assignments.id', '=', 'source_charts.assignment_id')
            ->join('vehicles as source_vehicles', 'source_vehicles.id', '=', 'source_chart_assignments.vehicle_id')
            ->where('calculation_sources.active_marker', true)
            ->selectRaw(
                'calculation_sources.calculation_id, COUNT(DISTINCT source_vehicles.id) as vehicle_count, '
                .'MIN(COALESCE(source_vehicles.registration_number, source_vehicles.vehicle_number)) as first_vehicle'
            )
            ->groupBy('calculation_sources.calculation_id');

        $customerSide = $side === RentalCalculationSide::Customer;
        $query = DB::table('invoices')
            ->join('invoice_sources', function ($join): void {
                $join->on('invoice_sources.invoice_id', '=', 'invoices.id')
                    ->where('invoice_sources.source_type', VehicleRentalSource::CALCULATION_DOCUMENT);
            })
            ->join('vehicle_rental_calculations as calculations', 'calculations.id', '=', 'invoice_sources.source_id')
            ->join('vehicle_rental_agreements as agreements', 'agreements.id', '=', 'calculations.agreement_id')
            ->leftJoin('customers', function ($join) use ($customerSide): void {
                $join->on('customers.id', '=', 'agreements.customer_id');
                if (! $customerSide) {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('suppliers', function ($join) use ($customerSide): void {
                $join->on('suppliers.id', '=', 'agreements.supplier_id');
                if ($customerSide) {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('currencies', 'currencies.id', '=', 'invoices.currency_id')
            ->leftJoinSub($vehicleTotals, 'vehicle_totals', 'vehicle_totals.calculation_id', '=', 'calculations.id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.invoice_type', InvoiceType::Rental->value)
            ->where('invoices.direction', $customerSide ? InvoiceDirection::Outbound->value : InvoiceDirection::Inbound->value)
            ->where('calculations.side', $side->value)
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.status',
                'invoices.subtotal',
                'invoices.tax_total',
                'invoices.adjustment_total',
                'invoices.grand_total',
                'invoices.paid_total',
                'invoices.balance_due',
                'calculations.id as calculation_id',
                'calculations.calculation_number',
                'calculations.period_start',
                'calculations.period_end',
                'calculations.chart_count',
                'calculations.operating_days',
                'calculations.commercial_km',
                'calculations.excess_km',
                'agreements.id as agreement_id',
                'agreements.agreement_number',
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'suppliers.id as supplier_id',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'currencies.code as currency_code',
                'vehicle_totals.vehicle_count',
                'vehicle_totals.first_vehicle',
            ]);

        $this->contextScope($query, 'invoices', $params);
        $this->dateRange($query, 'invoices.invoice_date', $params);
        if (! empty($params['invoice_status'])) {
            $query->where('invoices.status', (string) $params['invoice_status']);
        } else {
            $query->whereIn('invoices.status', self::FINANCIAL_INVOICE_STATUSES);
        }
        if (! empty($params['agreement_id'])) {
            $query->where('agreements.id', (int) $params['agreement_id']);
        }
        if (! empty($params['customer_id'])) {
            $query->where('agreements.customer_id', (int) $params['customer_id']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where('agreements.supplier_id', (int) $params['supplier_id']);
        }
        if (! empty($params['vehicle_id'])) {
            $vehicleId = (int) $params['vehicle_id'];
            $query->whereExists(function (Builder $vehicleQuery) use ($vehicleId): void {
                $vehicleQuery->selectRaw('1')
                    ->from('vehicle_rental_calculation_sources as filtered_sources')
                    ->join('vehicle_rental_running_charts as filtered_charts', 'filtered_charts.id', '=', 'filtered_sources.running_chart_id')
                    ->join('vehicle_rental_assignments as filtered_assignments', 'filtered_assignments.id', '=', 'filtered_charts.assignment_id')
                    ->whereColumn('filtered_sources.calculation_id', 'calculations.id')
                    ->where('filtered_sources.active_marker', true)
                    ->where('filtered_assignments.vehicle_id', $vehicleId);
            });
        }
        $this->search($query, $params, [
            'invoices.invoice_number',
            'calculations.calculation_number',
            'agreements.agreement_number',
            'customers.code',
            'customers.name',
            'suppliers.code',
            'suppliers.name',
            'vehicle_totals.first_vehicle',
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
                'assignments.id',
                'assignments.status',
                'assignments.starts_at',
                'assignments.ends_at',
                'assignments.handover_odometer',
                'assignments.return_odometer',
                'assignments.self_drive',
                'assignments.replacement_reason',
                'customer_agreements.id as customer_agreement_id',
                'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id',
                'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'vehicles.id as vehicle_id',
                'vehicles.vehicle_number',
                'vehicles.registration_number',
                'drivers.id as driver_id',
                'drivers.employee_number',
                'drivers.display_name as driver_name',
                'replaced_vehicles.vehicle_number as replaced_vehicle_number',
                'replaced_vehicles.registration_number as replaced_registration_number',
                DB::raw('COALESCE(chart_totals.chart_count, 0) as chart_count'),
                DB::raw('COALESCE(chart_totals.total_km, 0) as total_km'),
                DB::raw('COALESCE(chart_totals.garage_km, 0) as garage_km'),
                DB::raw('COALESCE(chart_totals.commercial_km, 0) as commercial_km'),
            ]);

        $this->contextScope($query, 'assignments', $params);
        $this->overlapRange($query, 'assignments.starts_at', 'assignments.ends_at', $params);
        $this->commonRentalFilters($query, $params, 'assignments', 'customer_agreements', 'owner_agreements');
        if (! empty($params['assignment_status'])) {
            $query->where('assignments.status', (string) $params['assignment_status']);
        }
        if (! empty($params['driver_employee_id'])) {
            $query->where('assignments.driver_employee_id', (int) $params['driver_employee_id']);
        }
        $this->search($query, $params, [
            'customer_agreements.agreement_number',
            'owner_agreements.agreement_number',
            'customers.code',
            'customers.name',
            'suppliers.code',
            'suppliers.name',
            'vehicles.vehicle_number',
            'vehicles.registration_number',
            'drivers.employee_number',
            'drivers.display_name',
            'assignments.replacement_reason',
        ]);

        return $query;
    }

    private function summary(string $key, Builder $query): array
    {
        return match ($key) {
            self::RUNNING_CHART => $this->runningChartSummary($query),
            self::CUSTOMER_INVOICES, self::OWNER_VOUCHERS => $this->financialDocumentSummary($query),
            self::RENTAL_HISTORY => $this->rentalHistorySummary($query),
            default => [],
        };
    }

    /** @return array<string, int|string> */
    private function runningChartSummary(Builder $query): array
    {
        $totals = $query->select([])->selectRaw(
            "COUNT(charts.id) as chart_count, "
            ."SUM(CASE WHEN charts.status = 'finalized' THEN 1 ELSE 0 END) as finalized_count, "
            ."SUM(CASE WHEN charts.status = 'draft' THEN 1 ELSE 0 END) as draft_count, "
            ."SUM(CASE WHEN charts.status = 'reversed' THEN 1 ELSE 0 END) as reversed_count, "
            .'COALESCE(SUM(charts.total_km), 0) as total_km, '
            .'COALESCE(SUM(charts.garage_km), 0) as garage_km, '
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
            'total_km' => $this->decimal($totals->total_km ?? 0),
            'garage_km' => $this->decimal($totals->garage_km ?? 0),
            'commercial_km' => $this->decimal($totals->commercial_km ?? 0),
            'normal_ot_hours' => $this->decimal($totals->normal_ot_hours ?? 0),
            'double_ot_hours' => $this->decimal($totals->double_ot_hours ?? 0),
            'triple_ot_hours' => $this->decimal($totals->triple_ot_hours ?? 0),
            'night_outs' => (int) ($totals->night_outs ?? 0),
        ];
    }

    /** @return array<string, int|string> */
    private function financialDocumentSummary(Builder $query): array
    {
        $totals = $query->select([])->selectRaw(
            'COUNT(DISTINCT invoices.id) as document_count, '
            .'COALESCE(SUM(invoices.subtotal), 0) as subtotal, '
            .'COALESCE(SUM(invoices.tax_total), 0) as tax_total, '
            .'COALESCE(SUM(invoices.adjustment_total), 0) as adjustment_total, '
            .'COALESCE(SUM(invoices.grand_total), 0) as grand_total, '
            .'COALESCE(SUM(invoices.paid_total), 0) as paid_total, '
            .'COALESCE(SUM(invoices.balance_due), 0) as balance_due'
        )->first();

        return [
            'documents' => (int) ($totals->document_count ?? 0),
            'subtotal' => $this->decimal($totals->subtotal ?? 0),
            'tax' => $this->decimal($totals->tax_total ?? 0),
            'adjustments' => $this->decimal($totals->adjustment_total ?? 0),
            'grand_total' => $this->decimal($totals->grand_total ?? 0),
            'paid' => $this->decimal($totals->paid_total ?? 0),
            'outstanding' => $this->decimal($totals->balance_due ?? 0),
        ];
    }

    /** @return array<string, int|string> */
    private function rentalHistorySummary(Builder $query): array
    {
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
            'total_km' => $this->decimal($totals->total_km ?? 0),
            'commercial_km' => $this->decimal($totals->commercial_km ?? 0),
        ];
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    private function runChartExceptions(array $params): array
    {
        $rows = collect($this->chartExceptionRows($params));
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
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $items,
            'summary' => $summary,
            'meta' => [
                'current_page' => $page,
                'from' => $items->isEmpty() ? null : (($page - 1) * $perPage) + 1,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $items->isEmpty() ? null : min($page * $perPage, $total),
                'total' => $total,
            ],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'report' => $this->chartExceptionDefinition()->toArray(),
        ];
    }

    /** @param array<string, mixed> $params @return list<array<string, mixed>> */
    private function chartExceptionRows(array $params): array
    {
        [$dateFrom, $dateTo] = $this->exceptionPeriod($params);
        if ($dateTo->lessThan($dateFrom)) {
            return [];
        }

        $assignmentsQuery = DB::table('vehicle_rental_assignments as assignments')
            ->join('vehicle_rental_agreements as customer_agreements', 'customer_agreements.id', '=', 'assignments.agreement_id')
            ->leftJoin('customers', 'customers.id', '=', 'customer_agreements.customer_id')
            ->leftJoin('vehicle_rental_assignments as source_assignments', 'source_assignments.id', '=', 'assignments.source_assignment_id')
            ->leftJoin('vehicle_rental_agreements as owner_agreements', 'owner_agreements.id', '=', 'source_assignments.agreement_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'owner_agreements.supplier_id')
            ->join('vehicles', 'vehicles.id', '=', 'assignments.vehicle_id')
            ->where('assignments.side', RentalAssignmentSide::CustomerUse->value)
            ->where('assignments.status', '!=', RentalAssignmentStatus::Cancelled->value)
            ->whereDate('assignments.starts_at', '<=', $dateTo->toDateString())
            ->where(function (Builder $period) use ($dateFrom): void {
                $period->whereNull('assignments.ends_at')
                    ->orWhereDate('assignments.ends_at', '>=', $dateFrom->toDateString());
            })
            ->select([
                'assignments.id',
                'assignments.vehicle_id',
                'assignments.starts_at',
                'assignments.ends_at',
                'customer_agreements.id as customer_agreement_id',
                'customer_agreements.agreement_number as customer_agreement_number',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'owner_agreements.id as owner_agreement_id',
                'owner_agreements.agreement_number as owner_agreement_number',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'vehicles.vehicle_number',
                'vehicles.registration_number',
            ]);
        $this->contextScope($assignmentsQuery, 'assignments', $params);
        $this->commonRentalFilters($assignmentsQuery, $params, 'assignments', 'customer_agreements', 'owner_agreements');
        $assignments = $assignmentsQuery->get();
        if ($assignments->isEmpty()) {
            return [];
        }

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
            $start = CarbonImmutable::parse($assignment->starts_at)->startOfDay();
            if ($start->lessThan($dateFrom)) {
                $start = $dateFrom;
            }
            $end = $assignment->ends_at === null
                ? $dateTo
                : CarbonImmutable::parse($assignment->ends_at)->startOfDay();
            if ($end->greaterThan($dateTo)) {
                $end = $dateTo;
            }
            if ($end->lessThan($start)) {
                continue;
            }

            for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
                $key = (int) $assignment->id.'|'.$date->toDateString();
                $dayCharts = $chartsByAssignmentDate->get($key, collect());
                if ($dayCharts->isEmpty()) {
                    $rows[] = $this->exceptionRow(
                        'missing_chart',
                        $date->toDateString(),
                        $assignment,
                        0,
                        [],
                        'The active customer-use assignment has no current Running Chart for this operational date.',
                    );
                } elseif ($dayCharts->count() > 1) {
                    $rows[] = $this->exceptionRow(
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

        $vehicleDateGroups = $charts->groupBy(function (object $chart) use ($assignmentsById): string {
            $assignment = $assignmentsById->get($chart->assignment_id);
            return (int) ($assignment->vehicle_id ?? 0).'|'.CarbonImmutable::parse($chart->operational_date)->toDateString();
        });
        foreach ($vehicleDateGroups as $vehicleDate => $vehicleCharts) {
            $assignmentIds = $vehicleCharts->pluck('assignment_id')->unique();
            if ($assignmentIds->count() < 2) {
                continue;
            }
            [$vehicleId, $date] = explode('|', (string) $vehicleDate, 2);
            $firstAssignment = $assignments->firstWhere('vehicle_id', (int) $vehicleId);
            if ($firstAssignment === null) {
                continue;
            }
            $rows[] = $this->exceptionRow(
                'duplicate_vehicle_date',
                $date,
                $firstAssignment,
                $vehicleCharts->count(),
                $vehicleCharts->pluck('chart_number')->filter()->values()->all(),
                'The same physical vehicle has current Running Charts under multiple customer-use assignments on this date.',
                'Multiple assignments',
            );
        }

        $rows = collect($rows);
        if (! empty($params['exception_type'])) {
            $rows = $rows->where('exception_type', (string) $params['exception_type']);
        }
        $term = trim((string) ($params['search'] ?? ''));
        if ($term !== '') {
            $needle = mb_strtolower($term);
            $rows = $rows->filter(static function (array $row) use ($needle): bool {
                return str_contains(mb_strtolower(implode(' ', array_map('strval', $row))), $needle);
            });
        }

        $sort = (string) ($params['sort'] ?? 'operational_date');
        $direction = (string) ($params['direction'] ?? 'asc');
        $allowed = ['operational_date', 'exception_type', 'vehicle', 'customer', 'customer_agreement', 'chart_count'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'operational_date';
        $rows = $direction === 'desc'
            ? $rows->sortByDesc($sort, SORT_NATURAL | SORT_FLAG_CASE)
            : $rows->sortBy($sort, SORT_NATURAL | SORT_FLAG_CASE);

        return $rows->values()->all();
    }

    /** @return array<string, mixed> */
    private function exceptionRow(
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
            'vehicle' => $this->vehicle($assignment->registration_number ?? null, $assignment->vehicle_number ?? null),
            'assignment' => 'Assignment #'.(int) $assignment->id,
            'customer' => $customer ?? $this->party($assignment->customer_code ?? null, $assignment->customer_name ?? null),
            'customer_agreement' => $assignment->customer_agreement_number,
            'owner' => $this->party($assignment->supplier_code ?? null, $assignment->supplier_name ?? null),
            'owner_agreement' => $assignment->owner_agreement_number,
            'chart_count' => $chartCount,
            'chart_numbers' => implode(', ', $chartNumbers),
            'explanation' => $explanation,
        ];
    }

    /** @param array<string, mixed> $params @return array{CarbonImmutable, CarbonImmutable} */
    private function exceptionPeriod(array $params): array
    {
        if (empty($params['date_from']) || empty($params['date_to'])) {
            throw ValidationException::withMessages([
                'date_from' => ['The missing and duplicate Running Chart report requires a start and end date.'],
                'date_to' => ['The missing and duplicate Running Chart report requires a start and end date.'],
            ]);
        }
        $from = CarbonImmutable::parse((string) $params['date_from'])->startOfDay();
        $requestedTo = CarbonImmutable::parse((string) $params['date_to'])->startOfDay();
        if ($from->diffInDays($requestedTo) + 1 > self::MAX_EXCEPTION_REPORT_DAYS) {
            throw ValidationException::withMessages([
                'date_to' => ['The Running Chart exception report cannot exceed '.self::MAX_EXCEPTION_REPORT_DAYS.' calendar days.'],
            ]);
        }

        return [$from, $requestedTo->min(CarbonImmutable::today())];
    }

    private function row(string $key, object $row): array
    {
        return match ($key) {
            self::RUNNING_CHART => $this->runningChartRow($row),
            self::CUSTOMER_INVOICES => $this->financialDocumentRow($row, true),
            self::OWNER_VOUCHERS => $this->financialDocumentRow($row, false),
            self::RENTAL_HISTORY => $this->rentalHistoryRow($row),
            default => throw new InvalidArgumentException("Vehicle Rental report row [{$key}] is not defined."),
        };
    }

    /** @return array<string, mixed> */
    private function runningChartRow(object $row): array
    {
        return [
            'operational_date' => $this->date($row->operational_date),
            'chart_number' => $row->chart_number,
            'status' => $row->status,
            'customer' => $this->party($row->customer_code, $row->customer_name),
            'customer_agreement' => $row->customer_agreement_number,
            'owner' => $this->party($row->supplier_code, $row->supplier_name),
            'owner_agreement' => $row->owner_agreement_number,
            'vehicle' => $this->vehicle($row->registration_number, $row->vehicle_number),
            'driver' => $this->party($row->employee_number, $row->driver_name),
            'starts_at' => $this->dateTime($row->starts_at),
            'ends_at' => $this->dateTime($row->ends_at),
            'start_odometer' => $this->nullableDecimal($row->start_odometer),
            'end_odometer' => $this->nullableDecimal($row->end_odometer),
            'total_km' => $this->decimal($row->total_km),
            'garage_km' => $this->decimal($row->garage_km),
            'commercial_km' => $this->decimal($row->commercial_km),
            'normal_ot_hours' => $this->decimal($row->normal_overtime_hours),
            'double_ot_hours' => $this->decimal($row->double_overtime_hours),
            'triple_ot_hours' => $this->decimal($row->triple_overtime_hours),
            'night_outs' => (int) $row->night_out_count,
            'origin' => $row->trip_origin,
            'destination' => $row->trip_destination,
            'purpose' => $row->purpose,
            'replaces_chart' => $row->replaced_chart_number,
            'remarks' => $row->remarks,
        ];
    }

    /** @return array<string, mixed> */
    private function financialDocumentRow(object $row, bool $customerSide): array
    {
        $vehicleCount = (int) ($row->vehicle_count ?? 0);
        $vehicle = $vehicleCount > 1 ? $vehicleCount.' vehicles' : ($row->first_vehicle ?? null);

        return [
            'document_date' => $this->date($row->invoice_date),
            'document_number' => $row->invoice_number,
            'status' => $row->status,
            'party' => $customerSide
                ? $this->party($row->customer_code, $row->customer_name)
                : $this->party($row->supplier_code, $row->supplier_name),
            'agreement' => $row->agreement_number,
            'vehicle' => $vehicle,
            'period_start' => $this->date($row->period_start),
            'period_end' => $this->date($row->period_end),
            'calculation_number' => $row->calculation_number,
            'running_charts' => (int) $row->chart_count,
            'operating_days' => (int) $row->operating_days,
            'commercial_km' => $this->decimal($row->commercial_km),
            'excess_km' => $this->decimal($row->excess_km),
            'subtotal' => $this->decimal($row->subtotal),
            'tax' => $this->decimal($row->tax_total),
            'adjustments' => $this->decimal($row->adjustment_total),
            'grand_total' => $this->decimal($row->grand_total),
            'paid' => $this->decimal($row->paid_total),
            'balance_due' => $this->decimal($row->balance_due),
            'currency' => $row->currency_code,
            'due_date' => $this->date($row->due_date),
        ];
    }

    /** @return array<string, mixed> */
    private function rentalHistoryRow(object $row): array
    {
        return [
            'assignment' => 'Assignment #'.(int) $row->id,
            'starts_at' => $this->dateTime($row->starts_at),
            'ends_at' => $this->dateTime($row->ends_at),
            'status' => $row->status,
            'customer' => $this->party($row->customer_code, $row->customer_name),
            'customer_agreement' => $row->customer_agreement_number,
            'owner' => $this->party($row->supplier_code, $row->supplier_name),
            'owner_agreement' => $row->owner_agreement_number,
            'vehicle' => $this->vehicle($row->registration_number, $row->vehicle_number),
            'driver_mode' => (bool) $row->self_drive
                ? 'Self-drive'
                : $this->party($row->employee_number, $row->driver_name),
            'handover_odometer' => $this->nullableDecimal($row->handover_odometer),
            'return_odometer' => $this->nullableDecimal($row->return_odometer),
            'replaced_vehicle' => $this->vehicle($row->replaced_registration_number, $row->replaced_vehicle_number),
            'replacement_reason' => $row->replacement_reason,
            'running_charts' => (int) $row->chart_count,
            'total_km' => $this->decimal($row->total_km),
            'garage_km' => $this->decimal($row->garage_km),
            'commercial_km' => $this->decimal($row->commercial_km),
        ];
    }

    /** @param array<string, mixed> $params */
    private function sort(string $key, Builder $query, array $params): void
    {
        $maps = [
            self::RUNNING_CHART => [
                'operational_date' => 'charts.operational_date',
                'chart_number' => 'charts.chart_number',
                'status' => 'charts.status',
                'customer' => 'customers.name',
                'vehicle' => 'vehicles.registration_number',
                'commercial_km' => 'charts.commercial_km',
            ],
            self::CUSTOMER_INVOICES => $this->financialSortMap(),
            self::OWNER_VOUCHERS => $this->financialSortMap(),
            self::RENTAL_HISTORY => [
                'assignment' => 'assignments.id',
                'starts_at' => 'assignments.starts_at',
                'ends_at' => 'assignments.ends_at',
                'status' => 'assignments.status',
                'customer' => 'customers.name',
                'owner' => 'suppliers.name',
                'vehicle' => 'vehicles.registration_number',
                'commercial_km' => 'chart_totals.commercial_km',
            ],
        ];
        $defaults = [
            self::RUNNING_CHART => 'charts.operational_date',
            self::CUSTOMER_INVOICES => 'invoices.invoice_date',
            self::OWNER_VOUCHERS => 'invoices.invoice_date',
            self::RENTAL_HISTORY => 'assignments.starts_at',
        ];
        $map = $maps[$key] ?? [];
        $sort = (string) ($params['sort'] ?? '');
        $column = $map[$sort] ?? $defaults[$key] ?? 'id';
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($column, $direction);
        if (! str_ends_with($column, '.id')) {
            $query->orderBy($key === self::RENTAL_HISTORY ? 'assignments.id' : ($key === self::RUNNING_CHART ? 'charts.id' : 'invoices.id'), $direction);
        }
    }

    /** @return array<string, string> */
    private function financialSortMap(): array
    {
        return [
            'document_date' => 'invoices.invoice_date',
            'document_number' => 'invoices.invoice_number',
            'status' => 'invoices.status',
            'party' => 'invoices.party_id',
            'agreement' => 'agreements.agreement_number',
            'period_start' => 'calculations.period_start',
            'period_end' => 'calculations.period_end',
            'grand_total' => 'invoices.grand_total',
            'paid' => 'invoices.paid_total',
            'balance_due' => 'invoices.balance_due',
        ];
    }

    /** @param array<string, mixed> $params */
    private function commonRentalFilters(
        Builder $query,
        array $params,
        string $assignmentAlias,
        string $customerAgreementAlias,
        string $ownerAgreementAlias,
    ): void {
        if (! empty($params['customer_id'])) {
            $query->where($customerAgreementAlias.'.customer_id', (int) $params['customer_id']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where($ownerAgreementAlias.'.supplier_id', (int) $params['supplier_id']);
        }
        if (! empty($params['vehicle_id'])) {
            $query->where($assignmentAlias.'.vehicle_id', (int) $params['vehicle_id']);
        }
        if (! empty($params['agreement_id'])) {
            $agreementId = (int) $params['agreement_id'];
            $query->where(function (Builder $agreement) use ($customerAgreementAlias, $ownerAgreementAlias, $agreementId): void {
                $agreement->where($customerAgreementAlias.'.id', $agreementId)
                    ->orWhere($ownerAgreementAlias.'.id', $agreementId);
            });
        }
    }

    /** @param array<string, mixed> $params */
    private function contextScope(Builder $query, string $alias, array $params): void
    {
        $query->where($alias.'.tenant_id', (int) $params['tenant_id']);
        $organizationUnitId = $params['organization_unit_id'] ?? null;
        if ($organizationUnitId === null) {
            $query->whereNull($alias.'.organization_unit_id');
        } else {
            $query->where($alias.'.organization_unit_id', (int) $organizationUnitId);
        }
    }

    /** @param array<string, mixed> $params */
    private function dateRange(Builder $query, string $column, array $params): void
    {
        if (! empty($params['date_from'])) {
            $query->whereDate($column, '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate($column, '<=', (string) $params['date_to']);
        }
    }

    /** @param array<string, mixed> $params */
    private function overlapRange(Builder $query, string $startColumn, string $endColumn, array $params): void
    {
        if (! empty($params['date_to'])) {
            $query->whereDate($startColumn, '<=', (string) $params['date_to']);
        }
        if (! empty($params['date_from'])) {
            $query->where(function (Builder $period) use ($endColumn, $params): void {
                $period->whereNull($endColumn)
                    ->orWhereDate($endColumn, '>=', (string) $params['date_from']);
            });
        }
    }

    /** @param array<string, mixed> $params @param list<string> $columns */
    private function search(Builder $query, array $params, array $columns): void
    {
        $term = trim((string) ($params['search'] ?? ''));
        if ($term === '') {
            return;
        }
        $like = '%'.$term.'%';
        $query->where(function (Builder $search) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $index === 0 ? $search->where($column, 'like', $like) : $search->orWhere($column, 'like', $like);
            }
        });
    }

    private function runningChartDefinition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::RUNNING_CHART,
            title: 'Daily Running Chart Report',
            group: 'Vehicle Rental — Operations',
            model: RentalRunningChart::class,
            columns: [
                new ReportColumn('operational_date', 'Date', sortBy: 'operational_date', format: 'date'),
                new ReportColumn('chart_number', 'Running Chart', sortBy: 'chart_number'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('driver', 'Driver'),
                new ReportColumn('starts_at', 'Start Time', format: 'datetime'),
                new ReportColumn('ends_at', 'End Time', format: 'datetime'),
                new ReportColumn('start_odometer', 'Start KM', format: 'decimal'),
                new ReportColumn('end_odometer', 'End KM', format: 'decimal'),
                new ReportColumn('total_km', 'Total KM', format: 'decimal', summarize: true),
                new ReportColumn('garage_km', 'Garage KM', format: 'decimal', summarize: true),
                new ReportColumn('commercial_km', 'Commercial KM', sortBy: 'commercial_km', format: 'decimal', summarize: true),
                new ReportColumn('normal_ot_hours', 'Normal OT', format: 'decimal', summarize: true),
                new ReportColumn('double_ot_hours', 'Double OT', format: 'decimal', summarize: true),
                new ReportColumn('triple_ot_hours', 'Triple OT', format: 'decimal', summarize: true),
                new ReportColumn('night_outs', 'Night-Outs', format: 'decimal', summarize: true),
                new ReportColumn('origin', 'Origin'),
                new ReportColumn('destination', 'Destination'),
                new ReportColumn('purpose', 'Purpose'),
                new ReportColumn('replaces_chart', 'Replaces Chart'),
                new ReportColumn('remarks', 'Remarks'),
            ],
            dateColumn: 'operational_date',
            defaultSort: 'operational_date',
            defaultDirection: 'desc',
            description: 'Physical daily movement with customer, owner, vehicle, driver, kilometre and overtime context.',
            orientation: 'landscape',
        );
    }

    private function chartExceptionDefinition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::CHART_EXCEPTIONS,
            title: 'Missing / Duplicate Running Chart Exceptions',
            group: 'Vehicle Rental — Operations',
            model: RentalAssignment::class,
            columns: [
                new ReportColumn('operational_date', 'Date', sortBy: 'operational_date', format: 'date'),
                new ReportColumn('exception_type', 'Exception', sortBy: 'exception_type'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('assignment', 'Assignment'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('chart_count', 'Charts', sortBy: 'chart_count', format: 'decimal'),
                new ReportColumn('chart_numbers', 'Chart Numbers'),
                new ReportColumn('explanation', 'Explanation'),
            ],
            dateColumn: 'operational_date',
            defaultSort: 'operational_date',
            defaultDirection: 'asc',
            description: 'Past and current assignment dates with missing charts or duplicate current chart evidence. A date range is required.',
            orientation: 'landscape',
        );
    }

    private function customerInvoiceDefinition(): ReportDefinition
    {
        return $this->financialDocumentDefinition(
            self::CUSTOMER_INVOICES,
            'Customer Invoice Register',
            'Posted and reversed Vehicle Rental customer invoices traced to their calculations and Running Charts.',
        );
    }

    private function ownerVoucherDefinition(): ReportDefinition
    {
        return $this->financialDocumentDefinition(
            self::OWNER_VOUCHERS,
            'Owner Payable Voucher Register',
            'Posted and reversed self-billed Vehicle Rental owner settlements traced to their calculations and Running Charts.',
        );
    }

    private function financialDocumentDefinition(string $key, string $title, string $description): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental — Financial',
            model: Invoice::class,
            columns: [
                new ReportColumn('document_date', 'Document Date', sortBy: 'document_date', format: 'date'),
                new ReportColumn('document_number', $key === self::CUSTOMER_INVOICES ? 'Invoice No.' : 'Voucher No.', sortBy: 'document_number'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('party', $key === self::CUSTOMER_INVOICES ? 'Customer' : 'Vehicle Owner', sortBy: 'party'),
                new ReportColumn('agreement', 'Agreement', sortBy: 'agreement'),
                new ReportColumn('vehicle', 'Vehicle'),
                new ReportColumn('period_start', 'Period From', sortBy: 'period_start', format: 'date'),
                new ReportColumn('period_end', 'Period To', sortBy: 'period_end', format: 'date'),
                new ReportColumn('calculation_number', 'Calculation'),
                new ReportColumn('running_charts', 'Charts', format: 'decimal'),
                new ReportColumn('operating_days', 'Operating Days', format: 'decimal'),
                new ReportColumn('commercial_km', 'Commercial KM', format: 'decimal', summarize: true),
                new ReportColumn('excess_km', 'Excess KM', format: 'decimal', summarize: true),
                new ReportColumn('subtotal', 'Subtotal', format: 'money', summarize: true),
                new ReportColumn('tax', 'Tax', format: 'money', summarize: true),
                new ReportColumn('adjustments', 'Adjustments', format: 'money', summarize: true),
                new ReportColumn('grand_total', $key === self::CUSTOMER_INVOICES ? 'Invoice Total' : 'Net Payable', sortBy: 'grand_total', format: 'money', summarize: true),
                new ReportColumn('paid', 'Settled', sortBy: 'paid', format: 'money', summarize: true),
                new ReportColumn('balance_due', 'Outstanding', sortBy: 'balance_due', format: 'money', summarize: true),
                new ReportColumn('currency', 'Currency'),
                new ReportColumn('due_date', 'Due Date', format: 'date'),
            ],
            dateColumn: 'document_date',
            defaultSort: 'document_date',
            defaultDirection: 'desc',
            description: $description,
            orientation: 'landscape',
        );
    }

    private function rentalHistoryDefinition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::RENTAL_HISTORY,
            title: 'Vehicle Rental History',
            group: 'Vehicle Rental — Operations',
            model: RentalAssignment::class,
            columns: [
                new ReportColumn('assignment', 'Assignment', sortBy: 'assignment'),
                new ReportColumn('starts_at', 'Start', sortBy: 'starts_at', format: 'datetime'),
                new ReportColumn('ends_at', 'End', sortBy: 'ends_at', format: 'datetime'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner', sortBy: 'owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('driver_mode', 'Driver / Mode'),
                new ReportColumn('handover_odometer', 'Handover KM', format: 'decimal'),
                new ReportColumn('return_odometer', 'Return KM', format: 'decimal'),
                new ReportColumn('replaced_vehicle', 'Replaced Vehicle'),
                new ReportColumn('replacement_reason', 'Replacement Reason'),
                new ReportColumn('running_charts', 'Finalized Charts', format: 'decimal'),
                new ReportColumn('total_km', 'Total KM', format: 'decimal', summarize: true),
                new ReportColumn('garage_km', 'Garage KM', format: 'decimal', summarize: true),
                new ReportColumn('commercial_km', 'Commercial KM', sortBy: 'commercial_km', format: 'decimal', summarize: true),
            ],
            dateColumn: 'starts_at',
            defaultSort: 'starts_at',
            defaultDirection: 'desc',
            description: 'Customer-use assignment history with owner source, driver mode, replacement lineage and finalized Running Chart totals.',
            orientation: 'landscape',
        );
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? 0));
    }

    private function nullableDecimal(mixed $value): ?string
    {
        return $value === null ? null : $this->decimal($value);
    }

    private function party(mixed $code, mixed $name): ?string
    {
        $code = trim((string) $code);
        $name = trim((string) $name);
        $value = trim($code.' '.$name);

        return $value === '' ? null : $value;
    }

    private function vehicle(mixed $registrationNumber, mixed $vehicleNumber): ?string
    {
        $registrationNumber = trim((string) $registrationNumber);
        $vehicleNumber = trim((string) $vehicleNumber);

        return $registrationNumber !== '' ? $registrationNumber : ($vehicleNumber !== '' ? $vehicleNumber : null);
    }

    private function date(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toDateString();
    }

    private function dateTime(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toDateTimeString();
    }
}
