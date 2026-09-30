<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceLegacyHistory;

final class VehicleServiceHistoryReportService
{
    public function run(array $params): array
    {
        $vehicle = $this->vehicle($params);
        $index = $this->indexQuery($params, $vehicle);
        $latest = (clone $index)->first();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 10)));
        /** @var LengthAwarePaginator $paginator */
        $paginator = $index->paginate($perPage, ['*'], 'page', $page);

        $response = [
            'data' => $this->hydrateRows(collect($paginator->items()), $vehicle),
            'summary' => [
                'total_jobs' => $paginator->total(),
                'last_service_date' => $latest?->service_date,
                'last_service_mileage' => $latest?->odometer_reading,
                'next_service_mileage' => $latest?->next_service_mileage,
            ],
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
            'vehicle' => $vehicle === null ? null : $this->vehicleResource($vehicle),
        ];

        return $response;
    }

    public function exportRows(array $params): Collection
    {
        $vehicle = $this->vehicle($params);
        $query = $this->indexQuery($params, $vehicle);
        $count = (clone $query)->count();
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ($count > $limit) {
            throw ValidationException::withMessages(['filters' => ["The report contains {$count} rows. Narrow the filters to {$limit} rows or fewer before exporting."]]);
        }

        return $this->hydrateRows($query->get(), $vehicle);
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
                new ReportColumn('source_label', 'Source'),
                new ReportColumn('job_number', 'Job number'),
                new ReportColumn('job_type_label', 'Job type'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('status', 'Status'),
                new ReportColumn('odometer_reading', 'Odometer', format: 'decimal'),
                new ReportColumn('work_summary', 'Items / work'),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
            description: 'Current and imported legacy vehicle service history in one chronological report.',
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

    private function indexQuery(array $params, ?Vehicle $vehicle): Builder
    {
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = $params['organization_unit_id'] ?? null;
        $current = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId)
            ->selectRaw("'current' as source, id as record_id, job_date as service_date, odometer_reading, next_service_mileage");
        $legacy = VehicleServiceLegacyHistory::query()
            ->forContext($tenantId, $organizationUnitId)
            ->selectRaw("'legacy' as source, id as record_id, service_date, odometer_reading, next_service_mileage");

        if ($vehicle !== null) {
            $current->where('vehicle_id', $vehicle->getKey());
            $legacy->where('vehicle_id', $vehicle->getKey());
        }
        if (! filter_var($params['include_cancelled'] ?? false, FILTER_VALIDATE_BOOL)) {
            $current->where('status', '<>', 'cancelled');
            $legacy->where(fn ($query) => $query->whereNull('status_raw')->orWhere('status_raw', '<>', 'cancelled'));
        }
        if (! empty($params['date_from'])) {
            $current->whereDate('job_date', '>=', $params['date_from']);
            $legacy->whereDate('service_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $current->whereDate('job_date', '<=', $params['date_to']);
            $legacy->whereDate('service_date', '<=', $params['date_to']);
        }
        if (! empty($params['job_status'])) {
            $current->where('status', $params['job_status']);
            $legacy->where('status_raw', $params['job_status']);
        }

        return DB::query()
            ->fromSub($current->toBase()->unionAll($legacy->toBase()), 'service_history')
            ->orderByDesc('service_date')
            ->orderByDesc('record_id')
            ->orderBy('source');
    }

    /** @param Collection<int, object> $indexRows @return Collection<int, array<string, mixed>> */
    private function hydrateRows(Collection $indexRows, ?Vehicle $vehicle): Collection
    {
        $currentIds = $indexRows->where('source', 'current')->pluck('record_id')->map(fn ($id): int => (int) $id)->all();
        $legacyIds = $indexRows->where('source', 'legacy')->pluck('record_id')->map(fn ($id): int => (int) $id)->all();
        $current = VehicleServiceJob::query()
            ->with(['vehicle.make', 'vehicle.model', 'customer', 'supervisor', 'inspection.inspector', 'lines.item', 'lines.uom', 'lines.employeeAssignments.employee', 'invoiceLinks.invoice'])
            ->whereKey($currentIds)
            ->get()
            ->keyBy(fn (VehicleServiceJob $job): int => (int) $job->getKey());
        $legacy = VehicleServiceLegacyHistory::query()
            ->with(['vehicle.make', 'vehicle.model', 'customer', 'items.item', 'items.uom'])
            ->whereKey($legacyIds)
            ->get()
            ->keyBy(fn (VehicleServiceLegacyHistory $history): int => (int) $history->getKey());

        return $indexRows->map(function (object $index) use ($current, $legacy): array {
            $id = (int) $index->record_id;
            if ($index->source === 'legacy') {
                return $this->legacyRow($legacy->get($id));
            }

            return $this->currentRow($current->get($id));
        })->values();
    }

    private function currentRow(VehicleServiceJob $job): array
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
        $jobType = $job->type instanceof \BackedEnum ? $job->type->value : (string) $job->type;

        return [
            'id' => 'current:'.$job->getKey(),
            'live_job_id' => (int) $job->getKey(),
            'source' => 'current',
            'source_label' => 'Current System',
            'job_type' => $jobType,
            'job_type_label' => $this->humanize($jobType),
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
            'work_summary' => $lines->pluck('description')->implode(', '),
            'lines' => $lines->all(),
            'invoice_total' => number_format($invoiceTotal, 6, '.', ''),
            'paid_total' => number_format($paidTotal, 6, '.', ''),
            'balance_due' => number_format($balanceDue, 6, '.', ''),
        ];
    }

    private function legacyRow(VehicleServiceLegacyHistory $history): array
    {
        $lines = $history->items->map(fn ($item): array => [
            'id' => (int) $item->getKey(),
            'category' => (string) $item->category,
            'description' => (string) ($item->description ?: $item->item_name_snapshot ?: 'Service item'),
            'quantity' => (string) $item->quantity,
            'uom' => $item->uom_snapshot,
            'employees' => [],
        ])->values();
        $jobType = $history->job_type ?: $history->job_type_raw;

        return [
            'id' => 'legacy:'.$history->getKey(),
            'live_job_id' => null,
            'source' => 'legacy',
            'source_label' => 'Old System',
            'job_type' => $jobType,
            'job_type_label' => $this->humanize($jobType),
            'job_number' => (string) $history->legacy_job_number,
            'job_date' => $history->service_date?->toDateString(),
            'status' => (string) ($history->status_raw ?: 'not_recorded'),
            'vehicle_label' => $history->vehicle?->registration_number ?: $history->vehicle_registration_snapshot ?: $history->vehicle?->vehicle_number ?: 'Vehicle not recorded',
            'customer' => $history->customer === null ? null : ['id' => (int) $history->customer->getKey(), 'code' => $history->customer_code_snapshot, 'name' => $history->customer_name_snapshot],
            'customer_name' => (string) ($history->customer_name_snapshot ?? $history->customer?->display_name ?? $history->customer?->name ?? ''),
            'supervisor' => null,
            'supervisor_name' => '',
            'technicians' => [],
            'technician_names' => '',
            'odometer_reading' => $history->odometer_reading === null ? null : (string) $history->odometer_reading,
            'next_service_mileage' => $history->next_service_mileage === null ? null : (string) $history->next_service_mileage,
            'complaint' => null,
            'inspection_notes' => null,
            'diagnosis' => null,
            'recommended_work' => null,
            'work_summary' => $lines->pluck('description')->implode(', '),
            'lines' => $lines->all(),
            'invoice_total' => '0.000000',
            'paid_total' => '0.000000',
            'balance_due' => '0.000000',
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

    private function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
