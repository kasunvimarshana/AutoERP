<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use Modules\VehicleRental\Enums\RentalCalculationSide;

final class VehicleRentalFinancialReportService
{
    /** @var list<string> */
    private const FINANCIAL_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Reversed->value,
    ];

    public function __construct(
        private readonly OperationalReportResponseBuilder $responses,
        private readonly VehicleRentalReportValueFormatter $values,
    ) {}

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(string $key, array $params, ReportDefinition $definition): array
    {
        $query = $this->query($key, $params);
        $summary = $this->summary(clone $query);
        $this->sort($query, $params);

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
        $this->sort($query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($key, $row));
    }

    /** @param array<string, mixed> $params */
    private function query(string $key, array $params): Builder
    {
        $side = match ($key) {
            VehicleRentalReportService::CUSTOMER_INVOICES => RentalCalculationSide::Customer,
            VehicleRentalReportService::OWNER_VOUCHERS => RentalCalculationSide::Owner,
            default => throw new InvalidArgumentException("Financial Vehicle Rental report [{$key}] is not defined."),
        };
        $tenantId = (int) $params['tenant_id'];
        $customerSide = $side === RentalCalculationSide::Customer;
        $vehicleTotals = DB::table('vehicle_rental_calculation_sources as calculation_sources')
            ->join('vehicle_rental_running_charts as source_charts', 'source_charts.id', '=', 'calculation_sources.running_chart_id')
            ->join('vehicle_rental_assignments as source_assignments', 'source_assignments.id', '=', 'source_charts.assignment_id')
            ->join('vehicles as source_vehicles', 'source_vehicles.id', '=', 'source_assignments.vehicle_id')
            ->where('calculation_sources.tenant_id', $tenantId)
            ->where('calculation_sources.active_marker', true)
            ->selectRaw(
                'calculation_sources.calculation_id, COUNT(DISTINCT source_vehicles.id) as vehicle_count, '
                .'MIN(COALESCE(source_vehicles.registration_number, source_vehicles.vehicle_number)) as first_vehicle'
            )
            ->groupBy('calculation_sources.calculation_id');

        $query = DB::table('invoices')
            ->where('invoices.tenant_id', $tenantId)
            ->join('invoice_sources', function ($join): void {
                $join->on('invoice_sources.invoice_id', '=', 'invoices.id')
                    ->where('invoice_sources.source_type', VehicleRentalSource::CALCULATION_DOCUMENT);
            })
            ->join('vehicle_rental_calculations as calculations', 'calculations.id', '=', 'invoice_sources.source_id')
            ->join('vehicle_rental_agreements as agreements', 'agreements.id', '=', 'calculations.agreement_id')
            ->leftJoin('customers', 'customers.id', '=', 'agreements.customer_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'agreements.supplier_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'invoices.currency_id')
            ->leftJoinSub($vehicleTotals, 'vehicle_totals', 'vehicle_totals.calculation_id', '=', 'calculations.id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.invoice_type', InvoiceType::Rental->value)
            ->where('invoices.direction', $customerSide ? InvoiceDirection::Outbound->value : InvoiceDirection::Inbound->value)
            ->where('calculations.side', $side->value)
            ->select([
                'invoices.id', 'invoices.invoice_number', 'invoices.invoice_date', 'invoices.due_date', 'invoices.status',
                'invoices.subtotal', 'invoices.tax_total', 'invoices.adjustment_total', 'invoices.grand_total',
                'invoices.paid_total', 'invoices.balance_due',
                'calculations.id as calculation_id', 'calculations.calculation_number', 'calculations.period_start',
                'calculations.period_end', 'calculations.chart_count', 'calculations.operating_days',
                'calculations.commercial_km', 'calculations.excess_km',
                'agreements.id as agreement_id', 'agreements.agreement_number',
                'customers.id as customer_id', 'customers.code as customer_code', 'customers.name as customer_name',
                'suppliers.id as supplier_id', 'suppliers.code as supplier_code', 'suppliers.name as supplier_name',
                'currencies.code as currency_code', 'vehicle_totals.vehicle_count', 'vehicle_totals.first_vehicle',
            ]);

        $this->organizationScope($query, $params);
        $this->dateRange($query, $params);
        if (! empty($params['invoice_status'])) {
            $query->where('invoices.status', (string) $params['invoice_status']);
        } else {
            $query->whereIn('invoices.status', self::FINANCIAL_STATUSES);
        }
        if (! empty($params['agreement_id'])) $query->where('agreements.id', (int) $params['agreement_id']);
        if (! empty($params['customer_id'])) $query->where('agreements.customer_id', (int) $params['customer_id']);
        if (! empty($params['supplier_id'])) $query->where('agreements.supplier_id', (int) $params['supplier_id']);
        if (! empty($params['vehicle_id'])) {
            $vehicleId = (int) $params['vehicle_id'];
            $query->whereExists(function (Builder $vehicleQuery) use ($tenantId, $vehicleId): void {
                $vehicleQuery->selectRaw('1')
                    ->from('vehicle_rental_calculation_sources as filtered_sources')
                    ->join('vehicle_rental_running_charts as filtered_charts', 'filtered_charts.id', '=', 'filtered_sources.running_chart_id')
                    ->join('vehicle_rental_assignments as filtered_assignments', 'filtered_assignments.id', '=', 'filtered_charts.assignment_id')
                    ->whereColumn('filtered_sources.calculation_id', 'calculations.id')
                    ->where('filtered_sources.tenant_id', $tenantId)
                    ->where('filtered_sources.active_marker', true)
                    ->where('filtered_assignments.vehicle_id', $vehicleId);
            });
        }
        $this->search($query, $params);

        return $query;
    }

    /** @return array<string, int|string> */
    private function summary(Builder $query): array
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
            'subtotal' => $this->values->decimal($totals->subtotal ?? 0),
            'tax' => $this->values->decimal($totals->tax_total ?? 0),
            'adjustments' => $this->values->decimal($totals->adjustment_total ?? 0),
            'grand_total' => $this->values->decimal($totals->grand_total ?? 0),
            'paid' => $this->values->decimal($totals->paid_total ?? 0),
            'outstanding' => $this->values->decimal($totals->balance_due ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function row(string $key, object $row): array
    {
        $customerSide = $key === VehicleRentalReportService::CUSTOMER_INVOICES;
        $vehicleCount = (int) ($row->vehicle_count ?? 0);

        return [
            'document_date' => $this->values->date($row->invoice_date),
            'document_number' => $row->invoice_number,
            'status' => $row->status,
            'party' => $customerSide
                ? $this->values->party($row->customer_code, $row->customer_name)
                : $this->values->party($row->supplier_code, $row->supplier_name),
            'agreement' => $row->agreement_number,
            'vehicle' => $vehicleCount > 1 ? $vehicleCount.' vehicles' : ($row->first_vehicle ?? null),
            'period_start' => $this->values->date($row->period_start),
            'period_end' => $this->values->date($row->period_end),
            'calculation_number' => $row->calculation_number,
            'running_charts' => (int) $row->chart_count,
            'operating_days' => (int) $row->operating_days,
            'commercial_km' => $this->values->decimal($row->commercial_km),
            'excess_km' => $this->values->decimal($row->excess_km),
            'subtotal' => $this->values->decimal($row->subtotal),
            'tax' => $this->values->decimal($row->tax_total),
            'adjustments' => $this->values->decimal($row->adjustment_total),
            'grand_total' => $this->values->decimal($row->grand_total),
            'paid' => $this->values->decimal($row->paid_total),
            'balance_due' => $this->values->decimal($row->balance_due),
            'currency' => $row->currency_code,
            'due_date' => $this->values->date($row->due_date),
        ];
    }

    /** @param array<string, mixed> $params */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'document_date' => 'invoices.invoice_date', 'document_number' => 'invoices.invoice_number',
            'status' => 'invoices.status', 'party' => 'invoices.party_id', 'agreement' => 'agreements.agreement_number',
            'period_start' => 'calculations.period_start', 'period_end' => 'calculations.period_end',
            'grand_total' => 'invoices.grand_total', 'paid' => 'invoices.paid_total', 'balance_due' => 'invoices.balance_due',
        ];
        $column = $columns[(string) ($params['sort'] ?? '')] ?? 'invoices.invoice_date';
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($column, $direction)->orderBy('invoices.id', $direction);
    }

    /** @param array<string, mixed> $params */
    private function organizationScope(Builder $query, array $params): void
    {
        ($params['organization_unit_id'] ?? null) === null
            ? $query->whereNull('invoices.organization_unit_id')
            : $query->where('invoices.organization_unit_id', (int) $params['organization_unit_id']);
    }

    /** @param array<string, mixed> $params */
    private function dateRange(Builder $query, array $params): void
    {
        if (! empty($params['date_from'])) $query->whereDate('invoices.invoice_date', '>=', (string) $params['date_from']);
        if (! empty($params['date_to'])) $query->whereDate('invoices.invoice_date', '<=', (string) $params['date_to']);
    }

    /** @param array<string, mixed> $params */
    private function search(Builder $query, array $params): void
    {
        $term = trim((string) ($params['search'] ?? ''));
        if ($term === '') return;
        $columns = [
            'invoices.invoice_number', 'calculations.calculation_number', 'agreements.agreement_number',
            'customers.code', 'customers.name', 'suppliers.code', 'suppliers.name', 'vehicle_totals.first_vehicle',
        ];
        $query->where(function (Builder $scope) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                $index === 0
                    ? $scope->where($column, 'like', '%'.$term.'%')
                    : $scope->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
