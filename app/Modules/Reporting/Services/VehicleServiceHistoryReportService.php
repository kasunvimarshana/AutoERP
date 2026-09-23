<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceHistoryReportService
{
    public function __construct(private readonly OperationalReportResponseBuilder $responses) {}

    public function run(array $params): array
    {
        $vehicle = $this->vehicle($params);
        $query = $this->query($params, $vehicle);
        $latest = (clone $query)->reorder()->orderByDesc('job_date')->orderByDesc('id')->first();
        $response = $this->responses->paginate(
            $query,
            fn (VehicleServiceJob $job): array => $this->row($job, $vehicle),
            $this->definition(),
            [
                'total_jobs' => (clone $query)->count(),
                'last_service_date' => $latest?->job_date?->toDateString(),
                'last_service_mileage' => $latest?->odometer_reading === null ? null : (string) $latest->odometer_reading,
                'next_service_mileage' => $latest?->next_service_mileage === null ? null : (string) $latest->next_service_mileage,
            ],
            max(1, (int) ($params['page'] ?? 1)),
            min(50, max(1, (int) ($params['per_page'] ?? 10))),
        );
        $response['vehicle'] = $vehicle === null ? null : $this->vehicleResource($vehicle);

        return $response;
    }

    public function exportRows(array $params): Collection
    {
        $vehicle = $this->vehicle($params);

        return $this->responses->exportRows($this->query($params, $vehicle), fn (VehicleServiceJob $job): array => $this->row($job, $vehicle));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/service-history',
            title: 'Vehicle Service History',
            group: 'Vehicle Service',
            model: VehicleServiceJob::class,
            columns: [
                new ReportColumn('job_date', 'Service date', format: 'date'),
                new ReportColumn('job_number', 'Job number'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('status', 'Status'),
                new ReportColumn('odometer_reading', 'Odometer', format: 'decimal'),
                new ReportColumn('complaint', 'Customer complaint'),
                new ReportColumn('work_summary', 'Work performed'),
                new ReportColumn('technician_names', 'Technicians'),
                new ReportColumn('supervisor_name', 'Supervisor'),
                new ReportColumn('invoice_total', 'Invoice total', format: 'money', summarize: true),
                new ReportColumn('paid_total', 'Paid total', format: 'money', summarize: true),
                new ReportColumn('balance_due', 'Balance due', format: 'money', summarize: true),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
            description: 'Chronological Vehicle Service job history with optional vehicle filtering.',
            orientation: 'landscape',
        );
    }

    private function vehicle(array $params): ?Vehicle
    {
        if (empty($params['vehicle_id'])) {
            return null;
        }

        $vehicle = Vehicle::query()
            ->forTenant((int) $params['tenant_id'], $params['organization_unit_id'] ?? null)
            ->with(['make', 'model', 'currentOwnerships'])
            ->find($params['vehicle_id']);

        if ($vehicle === null) {
            throw ValidationException::withMessages(['vehicle_id' => ['The selected vehicle is not available in this reporting context.']]);
        }

        return $vehicle;
    }

    private function query(array $params, ?Vehicle $vehicle): Builder
    {
        $query = VehicleServiceJob::query()
            ->forContext((int) $params['tenant_id'], $params['organization_unit_id'] ?? null)
            ->with(['vehicle.make', 'vehicle.model', 'customer', 'supervisor', 'inspection.inspector', 'lines.item', 'lines.uom', 'lines.employeeAssignments.employee', 'invoiceLinks.invoice']);

        if ($vehicle !== null) {
            $query->where('vehicle_id', $vehicle->getKey());
        }

        if (! filter_var($params['include_cancelled'] ?? false, FILTER_VALIDATE_BOOL)) {
            $query->where('status', '<>', 'cancelled');
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('job_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('job_date', '<=', $params['date_to']);
        }
        if (! empty($params['job_status'])) {
            $query->where('status', $params['job_status']);
        }

        return $query->orderByDesc('job_date')->orderByDesc('id');
    }

    private function row(VehicleServiceJob $job, ?Vehicle $vehicle): array
    {
        $assignments = $job->lines->flatMap->employeeAssignments;
        $technicians = $assignments->map(fn ($assignment) => $this->employee($assignment->employee))->filter()->unique('id')->values();
        $lines = $job->lines->map(function ($line): array {
            $source = $line->line_source_type instanceof \BackedEnum ? $line->line_source_type->value : (string) $line->line_source_type;

            return [
                'id' => (int) $line->getKey(),
                'category' => in_array($source, ['inventory_item', 'external_item'], true) ? 'part' : 'work',
                'description' => (string) ($line->description ?: $line->item?->name ?: 'Service line'),
                'quantity' => (string) $line->quantity,
                'uom' => $line->uom?->code ?? $line->uom?->name,
                'employees' => $line->employeeAssignments->map(fn ($assignment) => $this->employee($assignment->employee))->filter()->values()->all(),
            ];
        })->values();
        $invoiceTotal = $job->invoiceLinks->sum(fn ($link) => (float) ($link->invoice?->grand_total ?? $link->invoice_total ?? 0));
        $paidTotal = $job->invoiceLinks->sum(fn ($link) => (float) ($link->invoice?->paid_total ?? 0));
        $balanceDue = $job->invoiceLinks->sum(fn ($link) => (float) ($link->invoice?->balance_due ?? 0));

        return [
            'id' => (int) $job->getKey(),
            'job_number' => (string) $job->job_number,
            'job_date' => $job->job_date?->toDateString(),
            'status' => $job->status instanceof \BackedEnum ? $job->status->value : (string) $job->status,
            'vehicle_label' => $job->vehicle?->registration_number ?: $job->vehicle?->vehicle_number ?: 'Vehicle not recorded',
            'customer' => $job->customer === null ? null : ['id' => (int) $job->customer->getKey(), 'code' => $job->customer->customer_number ?? $job->customer->code, 'name' => $job->customer->display_name ?? $job->customer->name],
            'customer_name' => (string) ($job->customer?->display_name ?? $job->customer?->name ?? ''),
            'supervisor' => $this->employee($job->supervisor),
            'supervisor_name' => (string) ($job->supervisor?->display_name ?? ''),
            'technicians' => $technicians->all(),
            'technician_names' => $technicians->pluck('name')->implode(', '),
            'odometer_reading' => $job->odometer_reading === null ? null : (string) $job->odometer_reading,
            'next_service_mileage' => $job->next_service_mileage === null ? null : (string) $job->next_service_mileage,
            'complaint' => $job->inspection?->customer_complaint,
            'inspection_notes' => $job->inspection?->inspection_notes,
            'diagnosis' => $job->inspection?->diagnosis,
            'recommended_work' => $job->inspection?->recommended_work,
            'work_summary' => $lines->where('category', 'work')->pluck('description')->implode(', '),
            'lines' => $lines->all(),
            'invoice_total' => number_format($invoiceTotal, 6, '.', ''),
            'paid_total' => number_format($paidTotal, 6, '.', ''),
            'balance_due' => number_format($balanceDue, 6, '.', ''),
        ];
    }

    private function vehicleResource(Vehicle $vehicle): array
    {
        $owner = $vehicle->currentOwnerships->first();

        return [
            'id' => (int) $vehicle->getKey(),
            'vehicle_number' => (string) $vehicle->vehicle_number,
            'registration_number' => $vehicle->registration_number,
            'display_name' => $vehicle->registration_number ?: $vehicle->vehicle_number,
            'make' => $vehicle->make?->name,
            'model' => $vehicle->model?->name,
            'manufacture_year' => $vehicle->manufacture_year,
            'odometer_reading' => (string) $vehicle->odometer_reading,
            'odometer_unit' => $vehicle->odometer_unit,
            'status' => $vehicle->status instanceof \BackedEnum ? $vehicle->status->value : (string) $vehicle->status,
            'current_owner' => $owner === null ? null : ['code' => $owner->owner_code_snapshot, 'name' => $owner->owner_name_snapshot],
        ];
    }

    private function employee(mixed $employee): ?array
    {
        return $employee === null ? null : ['id' => (int) $employee->getKey(), 'code' => $employee->employee_number, 'name' => $employee->display_name];
    }
}
