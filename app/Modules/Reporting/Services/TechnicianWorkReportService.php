<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;

final class TechnicianWorkReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $query = $this->filteredQuery($params);
        $totals = $this->totals(clone $query);
        $this->applySorting($query, $params);

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->with($this->relations())
            ->paginate($perPage, ['vehicle_service_line_employees.*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (VehicleServiceLineEmployee $assignment): array => $this->row($assignment))
                ->values(),
            'summary' => $totals,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'report' => $this->definition()->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->filteredQuery($params);
        $this->applySorting($query, $params);

        return $query
            ->with($this->relations())
            ->limit(5000)
            ->get(['vehicle_service_line_employees.*'])
            ->map(fn (VehicleServiceLineEmployee $assignment): array => $this->row($assignment));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/technician-work',
            title: 'Technician Work / Labour Assignment Commission',
            group: 'Vehicle Service',
            model: VehicleServiceLineEmployee::class,
            description: 'Technician labour work, assigned hours, commission and linked invoice/payment lifecycle.',
            columns: [
                new ReportColumn('job_number', 'Job number', 'job_number', 'job_number'),
                new ReportColumn('job_date', 'Job date', 'job_date', 'job_date', 'date'),
                new ReportColumn('operational_status', 'Operational status', 'operational_status', 'operational_status'),
                new ReportColumn('billing_status', 'Billing status', 'billing_status', 'billing_status'),
                new ReportColumn('payment_status', 'Payment status', 'payment_status', 'payment_status'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('line_description', 'Line description', 'line_description', 'line_description'),
                new ReportColumn('line_source_type', 'Line source type', 'line_source_type', 'line_source_type'),
                new ReportColumn('employee_name', 'Technician'),
                new ReportColumn('role_type', 'Role type', 'role_type', 'role_type'),
                new ReportColumn('assigned_hours', 'Assigned hours', 'assigned_hours', 'assigned_hours', 'decimal', true),
                new ReportColumn('rate', 'Rate', 'rate', 'rate', 'currency'),
                new ReportColumn('labour_amount', 'Labour amount', 'labour_amount', null, 'currency', true),
                new ReportColumn('line_total', 'Line total', 'line_total', 'line_total', 'currency', true),
                new ReportColumn('commission_type', 'Commission type', 'commission_type', 'commission_type'),
                new ReportColumn('commission_value', 'Commission value', 'commission_value', 'commission_value', 'decimal'),
                new ReportColumn('commission_amount', 'Commission amount', 'commission_amount', 'commission_amount', 'currency', true),
                new ReportColumn('supervisor_name', 'Supervisor'),
                new ReportColumn('supervisor_commission_amount', 'Supervisor commission', 'supervisor_commission_amount', 'supervisor_commission_amount', 'currency', true),
                new ReportColumn('invoice_status', 'Invoice status'),
                new ReportColumn('payment_document_status', 'Payment document status'),
                new ReportColumn('payment_posting_status', 'Payment posting status'),
                new ReportColumn('payment_allocation_status', 'Payment allocation status'),
                new ReportColumn('payment_instrument_status', 'Payment instrument status'),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Builder<VehicleServiceLineEmployee>
     */
    private function filteredQuery(array $params): Builder
    {
        $query = VehicleServiceLineEmployee::query()
            ->join('vehicle_service_jobs as jobs', 'jobs.id', '=', 'vehicle_service_line_employees.vehicle_service_job_id')
            ->join('vehicle_service_job_lines as lines', 'lines.id', '=', 'vehicle_service_line_employees.vehicle_service_job_line_id')
            ->where('vehicle_service_line_employees.tenant_id', (int) $params['tenant_id'])
            ->where('jobs.tenant_id', (int) $params['tenant_id'])
            ->where('lines.tenant_id', (int) $params['tenant_id']);

        if (array_key_exists('organization_unit_id', $params)) {
            $organizationUnitId = $params['organization_unit_id'];
            if ($organizationUnitId === null || $organizationUnitId === '') {
                $query
                    ->whereNull('vehicle_service_line_employees.organization_unit_id')
                    ->whereNull('jobs.organization_unit_id')
                    ->whereNull('lines.organization_unit_id');
            } else {
                $query
                    ->where('vehicle_service_line_employees.organization_unit_id', (int) $organizationUnitId)
                    ->where('jobs.organization_unit_id', (int) $organizationUnitId)
                    ->where('lines.organization_unit_id', (int) $organizationUnitId);
            }
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('jobs.job_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('jobs.job_date', '<=', (string) $params['date_to']);
        }

        $this->whereInteger($query, 'vehicle_service_line_employees.employee_id', $params['employee_id'] ?? null);
        $this->whereInteger($query, 'jobs.supervisor_employee_id', $params['supervisor_id'] ?? null);
        $this->whereInteger($query, 'jobs.customer_id', $params['customer_id'] ?? null);
        $this->whereInteger($query, 'jobs.vehicle_id', $params['vehicle_id'] ?? null);
        $this->whereString($query, 'jobs.operational_status', $params['operational_status'] ?? null);
        $this->whereString($query, 'jobs.billing_status', $params['billing_status'] ?? null);
        $this->whereString($query, 'jobs.payment_status', $params['payment_status'] ?? null);
        $this->whereString($query, 'vehicle_service_line_employees.role_type', $params['role_type'] ?? null);
        $this->whereString($query, 'vehicle_service_line_employees.commission_type', $params['commission_type'] ?? null);

        if (! empty($params['invoice_status'])) {
            $query->whereHas(
                'job.invoiceLinks.invoice',
                fn (Builder $invoice): Builder => $invoice->where('status', (string) $params['invoice_status']),
            );
        }

        if ($this->hasPaymentFilters($params)) {
            $query->whereHas('job.paymentLinks.payment', function (Builder $payment) use ($params): void {
                $this->whereString($payment, 'document_status', $params['payment_document_status'] ?? null);
                $this->whereString($payment, 'posting_status', $params['payment_posting_status'] ?? null);
                $this->whereString($payment, 'allocation_status', $params['payment_allocation_status'] ?? null);
                $this->whereString($payment, 'instrument_status', $params['payment_instrument_status'] ?? null);
            });
        }

        if (! empty($params['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $params['search']).'%';
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('jobs.job_number', 'like', $search)
                    ->orWhere('jobs.operational_status', 'like', $search)
                    ->orWhere('jobs.billing_status', 'like', $search)
                    ->orWhere('jobs.payment_status', 'like', $search)
                    ->orWhere('lines.description', 'like', $search)
                    ->orWhere('lines.line_source_type', 'like', $search)
                    ->orWhere('vehicle_service_line_employees.role_type', 'like', $search)
                    ->orWhereHas('employee', fn (Builder $employee): Builder => $employee
                        ->where('employee_number', 'like', $search)
                        ->orWhere('display_name', 'like', $search))
                    ->orWhereHas('job.customer', fn (Builder $customer): Builder => $customer
                        ->where('customer_number', 'like', $search)
                        ->orWhere('display_name', 'like', $search)
                        ->orWhere('name', 'like', $search))
                    ->orWhereHas('job.vehicle', fn (Builder $vehicle): Builder => $vehicle
                        ->where('vehicle_number', 'like', $search)
                        ->orWhere('registration_number', 'like', $search));
            });
        }

        return $query->select('vehicle_service_line_employees.*');
    }

    /**
     * @return array<string, string>
     */
    private function totals(Builder $query): array
    {
        $totalAssignedHours = '0.000000';
        $totalLabourAmount = '0.000000';
        $totalTechnicianCommission = '0.000000';
        $totalSupervisorCommission = '0.000000';
        $seenJobs = [];

        $query->with('job')->get(['vehicle_service_line_employees.*'])->each(function (VehicleServiceLineEmployee $assignment) use (
            &$totalAssignedHours,
            &$totalLabourAmount,
            &$totalTechnicianCommission,
            &$totalSupervisorCommission,
            &$seenJobs,
        ): void {
            $assignedHours = $this->decimal($assignment->assigned_hours);
            $rate = $this->decimal($assignment->rate);
            $totalAssignedHours = $this->math->add($totalAssignedHours, $assignedHours);
            $totalLabourAmount = $this->math->add($totalLabourAmount, $this->math->mul($assignedHours, $rate));
            $totalTechnicianCommission = $this->math->add(
                $totalTechnicianCommission,
                $this->decimal($assignment->commission_amount),
            );

            $job = $assignment->job;
            if ($job instanceof VehicleServiceJob && ! isset($seenJobs[$job->getKey()])) {
                $seenJobs[$job->getKey()] = true;
                $totalSupervisorCommission = $this->math->add(
                    $totalSupervisorCommission,
                    $this->decimal($job->supervisor_commission_amount),
                );
            }
        });

        return [
            'total_assigned_hours' => $totalAssignedHours,
            'total_labour_amount' => $totalLabourAmount,
            'total_technician_commission' => $totalTechnicianCommission,
            'total_supervisor_commission' => $totalSupervisorCommission,
            'total_payable_commission' => $this->math->add(
                $totalTechnicianCommission,
                $totalSupervisorCommission,
            ),
        ];
    }

    /**
     * @param  Builder<VehicleServiceLineEmployee>  $query
     * @param  array<string, mixed>  $params
     */
    private function applySorting(Builder $query, array $params): void
    {
        $columns = [
            'job_number' => 'jobs.job_number',
            'job_date' => 'jobs.job_date',
            'operational_status' => 'jobs.operational_status',
            'billing_status' => 'jobs.billing_status',
            'payment_status' => 'jobs.payment_status',
            'line_description' => 'lines.description',
            'line_source_type' => 'lines.line_source_type',
            'role_type' => 'vehicle_service_line_employees.role_type',
            'assigned_hours' => 'vehicle_service_line_employees.assigned_hours',
            'rate' => 'vehicle_service_line_employees.rate',
            'commission_type' => 'vehicle_service_line_employees.commission_type',
            'commission_value' => 'vehicle_service_line_employees.commission_value',
            'commission_amount' => 'vehicle_service_line_employees.commission_amount',
            'line_total' => 'lines.line_total',
            'supervisor_commission_amount' => 'jobs.supervisor_commission_amount',
        ];
        $sort = (string) ($params['sort'] ?? 'job_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query
            ->orderBy($columns[$sort] ?? $columns['job_date'], $direction)
            ->orderBy('vehicle_service_line_employees.id');
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'employee',
            'line',
            'job.customer',
            'job.vehicle',
            'job.supervisor',
            'job.invoiceLinks.invoice',
            'job.paymentLinks.payment',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(VehicleServiceLineEmployee $assignment): array
    {
        /** @var VehicleServiceJob|null $job */
        $job = $assignment->job;
        /** @var VehicleServiceJobLine|null $line */
        $line = $assignment->line;
        $invoice = $job instanceof VehicleServiceJob
            ? $job->invoiceLinks->pluck('invoice')->filter()->first()
            : null;
        $payment = $job instanceof VehicleServiceJob
            ? $job->paymentLinks->pluck('payment')->filter()->first()
            : null;
        $assignedHours = $this->decimal($assignment->assigned_hours);
        $rate = $this->decimal($assignment->rate);

        return [
            'id' => (int) $assignment->getKey(),
            'job_number' => (string) ($job?->job_number ?? ''),
            'job_date' => $job?->job_date?->toDateString(),
            'operational_status' => $this->enumValue($job?->operational_status),
            'billing_status' => $this->enumValue($job?->billing_status),
            'payment_status' => $this->enumValue($job?->payment_status),
            'customer' => $this->customerResource($job?->customer),
            'customer_name' => (string) ($job?->customer?->display_name ?? $job?->customer?->name ?? ''),
            'vehicle' => $this->vehicleResource($job?->vehicle),
            'vehicle_label' => (string) ($job?->vehicle?->registration_number ?? $job?->vehicle?->vehicle_number ?? ''),
            'line_description' => (string) ($line?->description ?? ''),
            'line_source_type' => $this->enumValue($line?->line_source_type),
            'employee' => $this->employeeResource($assignment->employee),
            'employee_name' => (string) ($assignment->employee?->display_name ?? ''),
            'role_type' => (string) $assignment->role_type,
            'assigned_hours' => $assignedHours,
            'rate' => $rate,
            'labour_amount' => $this->math->mul($assignedHours, $rate),
            'line_total' => $this->decimal($line?->line_total),
            'commission_type' => $this->enumValue($assignment->commission_type),
            'commission_value' => $this->decimal($assignment->commission_value),
            'commission_amount' => $this->decimal($assignment->commission_amount),
            'supervisor' => $this->employeeResource($job?->supervisor),
            'supervisor_name' => (string) ($job?->supervisor?->display_name ?? ''),
            'supervisor_commission_amount' => $this->decimal($job?->supervisor_commission_amount),
            'invoice_status' => $this->enumValue($invoice?->status),
            'invoice' => $this->invoiceResource($invoice),
            'payment_document_status' => $this->enumValue($payment?->document_status),
            'payment_posting_status' => $this->enumValue($payment?->posting_status),
            'payment_allocation_status' => $this->enumValue($payment?->allocation_status),
            'payment_instrument_status' => $this->enumValue($payment?->instrument_status),
            'payment' => $this->paymentResource($payment),
        ];
    }

    private function hasPaymentFilters(array $params): bool
    {
        foreach ([
            'payment_document_status',
            'payment_posting_status',
            'payment_allocation_status',
            'payment_instrument_status',
        ] as $filter) {
            if (($params[$filter] ?? null) !== null && $params[$filter] !== '') {
                return true;
            }
        }

        return false;
    }

    private function whereInteger(Builder $query, string $column, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $query->where($column, (int) $value);
        }
    }

    private function whereString(Builder $query, string $column, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $query->where($column, (string) $value);
        }
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }

    private function customerResource(?Customer $customer): ?array
    {
        if (! $customer instanceof Customer) {
            return null;
        }

        return [
            'id' => (int) $customer->getKey(),
            'code' => (string) ($customer->customer_number ?? $customer->code ?? ''),
            'name' => (string) ($customer->display_name ?? $customer->name ?? ''),
        ];
    }

    private function vehicleResource(?Vehicle $vehicle): ?array
    {
        if (! $vehicle instanceof Vehicle) {
            return null;
        }

        $number = (string) ($vehicle->vehicle_number ?? '');
        $registration = (string) ($vehicle->registration_number ?? '');

        return [
            'id' => (int) $vehicle->getKey(),
            'code' => $number,
            'name' => trim($registration !== '' ? $registration.' '.$number : $number),
        ];
    }

    private function employeeResource(?HrEmployee $employee): ?array
    {
        if (! $employee instanceof HrEmployee) {
            return null;
        }

        return [
            'id' => (int) $employee->getKey(),
            'code' => (string) ($employee->employee_number ?? $employee->code ?? ''),
            'name' => (string) ($employee->display_name ?? trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''))),
        ];
    }

    private function invoiceResource(?Invoice $invoice): ?array
    {
        if (! $invoice instanceof Invoice) {
            return null;
        }

        return [
            'id' => (int) $invoice->getKey(),
            'code' => (string) $invoice->invoice_number,
            'name' => (string) $invoice->invoice_number,
        ];
    }

    private function paymentResource(?Payment $payment): ?array
    {
        if (! $payment instanceof Payment) {
            return null;
        }

        return [
            'id' => (int) $payment->getKey(),
            'code' => (string) $payment->payment_number,
            'name' => (string) $payment->payment_number,
        ];
    }
}
