<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceJobType as VehicleServiceJobTypeEnum;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobType;

final class ServiceJobsByJobTypeReportService
{
    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = isset($params['organization_unit_id']) && is_numeric($params['organization_unit_id'])
            ? (int) $params['organization_unit_id']
            : null;

        $scopedQuery = $this->buildBaseQuery($params, $tenantId, $organizationUnitId);

        // Fetch all matching jobs for grouping & summary calculation
        $allJobs = (clone $scopedQuery)
            ->with([
                'jobType',
                'customer',
                'vehicle.make',
                'vehicle.model',
                'supervisor',
            ])
            ->orderBy('vehicle_service_jobs.job_date', 'desc')
            ->orderBy('vehicle_service_jobs.id', 'desc')
            ->get();

        $hydratedRows = $this->hydrateJobRows($allJobs);

        $summary = $this->calculateGlobalSummary($hydratedRows);
        $groups = $this->calculateGroups($hydratedRows, $summary['total_revenue'], $summary['total_jobs']);

        // Support pagination for flat jobs list if requested
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));
        $totalCount = $hydratedRows->count();
        $slicedData = $hydratedRows->slice(($page - 1) * $perPage, $perPage)->values();

        $lastPage = max(1, (int) ceil($totalCount / $perPage));

        return [
            'data' => $slicedData,
            'summary' => $summary,
            'groups' => $groups,
            'meta' => [
                'current_page' => $page,
                'from' => $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => min($page * $perPage, $totalCount),
                'total' => $totalCount,
            ],
            'links' => [
                'first' => "?page=1&per_page={$perPage}",
                'last' => "?page={$lastPage}&per_page={$perPage}",
                'prev' => $page > 1 ? '?page='.($page - 1)."&per_page={$perPage}" : null,
                'next' => $page < $lastPage ? '?page='.($page + 1)."&per_page={$perPage}" : null,
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
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = isset($params['organization_unit_id']) && is_numeric($params['organization_unit_id'])
            ? (int) $params['organization_unit_id']
            : null;

        $query = $this->buildBaseQuery($params, $tenantId, $organizationUnitId);

        $count = (clone $query)->count();
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ($count > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$count} rows. Narrow the filters to {$limit} rows or fewer before exporting."],
            ]);
        }

        $jobs = $query
            ->with([
                'jobType',
                'customer',
                'vehicle.make',
                'vehicle.model',
                'supervisor',
            ])
            ->orderBy('vehicle_service_jobs.job_date', 'desc')
            ->orderBy('vehicle_service_jobs.id', 'desc')
            ->get();

        $rows = $this->hydrateJobRows($jobs);

        // Sort by job type name, then date descending for structured export
        return $rows->sortBy([
            ['job_type_name', 'asc'],
            ['job_date', 'desc'],
        ])->values()->map(fn (array $row): array => [
            'job_type_name' => $row['job_type_name'],
            'job_type_code' => $row['job_type_code'],
            'job_number' => $row['job_number'],
            'job_date' => $row['job_date'],
            'customer_name' => $row['customer_name'],
            'customer_phone' => $row['customer_phone'] ?? '',
            'registration_number' => $row['registration_number'],
            'vehicle_label' => $row['vehicle_label'],
            'supervisor_name' => $row['supervisor_name'] ?? 'Unassigned',
            'status_label' => $row['status_label'],
            'odometer_reading' => $row['odometer_reading'] !== null ? number_format((float) $row['odometer_reading'], 0) : '',
            'subtotal' => number_format((float) $row['subtotal'], 2, '.', ''),
            'discount_total' => number_format((float) $row['discount_total'], 2, '.', ''),
            'tax_total' => number_format((float) $row['tax_total'], 2, '.', ''),
            'charge_total' => number_format((float) $row['charge_total'], 2, '.', ''),
            'grand_total' => number_format((float) $row['grand_total'], 2, '.', ''),
        ]);
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/jobs-by-type',
            title: 'Service Jobs by Job Type',
            group: 'Vehicle Service',
            model: VehicleServiceJob::class,
            columns: [
                new ReportColumn('job_type_name', 'Job type'),
                new ReportColumn('job_number', 'Job number'),
                new ReportColumn('job_date', 'Job date', format: 'date'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('registration_number', 'Registration no.'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('supervisor_name', 'Supervisor'),
                new ReportColumn('status_label', 'Status'),
                new ReportColumn('odometer_reading', 'Odometer', format: 'decimal'),
                new ReportColumn('subtotal', 'Subtotal', format: 'money', summarize: true),
                new ReportColumn('discount_total', 'Discount', format: 'money', summarize: true),
                new ReportColumn('tax_total', 'Tax', format: 'money', summarize: true),
                new ReportColumn('charge_total', 'Charges', format: 'money', summarize: true),
                new ReportColumn('grand_total', 'Total', format: 'money', summarize: true),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
            description: 'Comprehensive report of vehicle service jobs categorized and analyzed by Job Type.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildBaseQuery(array $params, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId);

        // Date range & date presets
        $this->applyDateFilters($query, $params);

        // Job type filter
        if (! empty($params['job_type_id'])) {
            $query->where('vehicle_service_jobs.job_type_id', (int) $params['job_type_id']);
        } elseif (! empty($params['job_type_code'])) {
            $code = (string) $params['job_type_code'];
            $query->where(function (Builder $q) use ($code): void {
                $q->whereHas('jobType', fn (Builder $jtq) => $jtq->where('code', $code))
                    ->orWhere('vehicle_service_jobs.type', $code);
            });
        }

        // Job status filter
        if (! empty($params['job_status']) && $params['job_status'] !== 'all') {
            $query->where('vehicle_service_jobs.status', $params['job_status']);
        } else {
            // By default exclude cancelled jobs unless explicitly requested via include_cancelled or job_status=cancelled
            $includeCancelled = filter_var($params['include_cancelled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $includeCancelled) {
                $query->where('vehicle_service_jobs.status', '<>', VehicleServiceJobStatus::Cancelled->value);
            }
        }

        // Customer filter
        if (! empty($params['customer_id'])) {
            $query->where('vehicle_service_jobs.customer_id', (int) $params['customer_id']);
        }

        // Vehicle filter
        if (! empty($params['vehicle_id'])) {
            $query->where('vehicle_service_jobs.vehicle_id', (int) $params['vehicle_id']);
        }

        // Supervisor filter
        if (! empty($params['supervisor_employee_id'])) {
            $query->where('vehicle_service_jobs.supervisor_employee_id', (int) $params['supervisor_employee_id']);
        }

        // Search term
        if (! empty($params['search'])) {
            $search = trim((string) $params['search']);
            $term = "%{$search}%";
            $query->where(function (Builder $q) use ($term): void {
                $q->where('vehicle_service_jobs.job_number', 'like', $term)
                    ->orWhereHas('vehicle', function (Builder $vq) use ($term): void {
                        $vq->where('registration_number', 'like', $term)
                            ->orWhere('vehicle_number', 'like', $term);
                    })
                    ->orWhereHas('customer', function (Builder $cq) use ($term): void {
                        $cq->where('name', 'like', $term)
                            ->orWhere('display_name', 'like', $term)
                            ->orWhere('phone', 'like', $term)
                            ->orWhere('customer_number', 'like', $term);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function applyDateFilters(Builder $query, array $params): void
    {
        $preset = (string) ($params['date_preset'] ?? '');
        $today = CarbonImmutable::now();

        if ($preset !== '') {
            match ($preset) {
                'today' => $query->whereDate('vehicle_service_jobs.job_date', '=', $today->toDateString()),
                'this_week' => $query->whereDate('vehicle_service_jobs.job_date', '>=', $today->startOfWeek()->toDateString())
                    ->whereDate('vehicle_service_jobs.job_date', '<=', $today->endOfWeek()->toDateString()),
                'this_month' => $query->whereDate('vehicle_service_jobs.job_date', '>=', $today->startOfMonth()->toDateString())
                    ->whereDate('vehicle_service_jobs.job_date', '<=', $today->endOfMonth()->toDateString()),
                'this_year' => $query->whereDate('vehicle_service_jobs.job_date', '>=', $today->startOfYear()->toDateString())
                    ->whereDate('vehicle_service_jobs.job_date', '<=', $today->endOfYear()->toDateString()),
                'all' => null,
                default => null,
            };
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('vehicle_service_jobs.job_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('vehicle_service_jobs.job_date', '<=', $params['date_to']);
        }
    }

    /**
     * @param  Collection<int, VehicleServiceJob>  $jobs
     * @return Collection<int, array<string, mixed>>
     */
    private function hydrateJobRows(Collection $jobs): Collection
    {
        $defaultTypes = VehicleServiceJobTypeEnum::defaults();

        return $jobs->map(function (VehicleServiceJob $job) use ($defaultTypes): array {
            /** @var VehicleServiceJobType|null $jobTypeModel */
            $jobTypeModel = $job->jobType;

            if ($jobTypeModel !== null) {
                $typeId = (int) $jobTypeModel->getKey();
                $typeCode = (string) $jobTypeModel->code;
                $typeName = (string) $jobTypeModel->name;
                $tracksMileage = (bool) $jobTypeModel->tracks_mileage;
            } elseif (! empty($job->type)) {
                $code = (string) $job->type;
                $defaultDef = $defaultTypes[$code] ?? null;
                $typeId = null;
                $typeCode = $code;
                $typeName = $defaultDef !== null ? (string) $defaultDef['name'] : ucwords(str_replace('_', ' ', $code));
                $tracksMileage = $defaultDef !== null ? (bool) $defaultDef['tracks_mileage'] : false;
            } else {
                $typeId = null;
                $typeCode = 'uncategorized';
                $typeName = 'Uncategorized';
                $tracksMileage = false;
            }

            $customer = $job->customer;
            $customerName = $customer?->display_name ?? $customer?->name ?? 'Unknown Customer';
            $customerPhone = $customer?->phone ?? null;

            $vehicle = $job->vehicle;
            $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? 'Unknown Vehicle';
            $makeName = $vehicle?->make?->name ?? '';
            $modelName = $vehicle?->model?->name ?? '';
            $makeModel = trim("{$makeName} {$modelName}");
            $vehicleLabel = $makeModel !== '' ? "{$regNo} ({$makeModel})" : $regNo;

            $supervisor = $job->supervisor;
            $supervisorName = $supervisor?->display_name ?? $supervisor?->name ?? null;

            $statusEnum = $job->status instanceof VehicleServiceJobStatus
                ? $job->status
                : VehicleServiceJobStatus::tryFrom((string) $job->status);

            $statusValue = $statusEnum?->value ?? (string) $job->status;
            $statusLabel = ucwords(str_replace('_', ' ', $statusValue));

            return [
                'id' => (int) $job->getKey(),
                'job_number' => (string) $job->job_number,
                'job_date' => $job->job_date?->toDateString() ?? '',
                'expected_delivery_date' => $job->expected_delivery_date?->toDateString(),
                'next_service_date' => $job->next_service_date?->toDateString(),
                'job_type_id' => $typeId,
                'job_type_code' => $typeCode,
                'job_type_name' => $typeName,
                'tracks_mileage' => $tracksMileage,
                'status' => $statusValue,
                'status_label' => $statusLabel,
                'customer' => [
                    'id' => $customer !== null ? (int) $customer->getKey() : (int) $job->customer_id,
                    'code' => $customer?->customer_number ?? '',
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'email' => $customer?->email ?? null,
                ],
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'vehicle' => [
                    'id' => $vehicle !== null ? (int) $vehicle->getKey() : (int) $job->vehicle_id,
                    'registration_number' => $regNo,
                    'vehicle_number' => $vehicle?->vehicle_number ?? '',
                    'display_name' => $vehicleLabel,
                    'make' => $makeName,
                    'model' => $modelName,
                    'year' => $vehicle?->year,
                ],
                'registration_number' => $regNo,
                'vehicle_label' => $vehicleLabel,
                'supervisor' => $supervisor !== null ? [
                    'id' => (int) $supervisor->getKey(),
                    'name' => $supervisorName ?? '',
                    'code' => $supervisor->employee_number ?? '',
                ] : null,
                'supervisor_name' => $supervisorName,
                'odometer_reading' => $job->odometer_reading !== null ? (string) $job->odometer_reading : null,
                'next_service_mileage' => $job->next_service_mileage !== null ? (string) $job->next_service_mileage : null,
                'subtotal' => (string) ($job->subtotal ?? self::ZERO),
                'discount_total' => (string) ($job->discount_total ?? self::ZERO),
                'tax_total' => (string) ($job->tax_total ?? self::ZERO),
                'charge_total' => (string) ($job->charge_total ?? self::ZERO),
                'grand_total' => (string) ($job->grand_total ?? self::ZERO),
                'commission_cost_total' => (string) ($job->commission_cost_total ?? self::ZERO),
                'net_after_commission' => (string) ($job->net_after_commission ?? self::ZERO),
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function calculateGlobalSummary(Collection $rows): array
    {
        $totalJobs = $rows->count();
        $totalSubtotal = self::ZERO;
        $totalDiscount = self::ZERO;
        $totalTax = self::ZERO;
        $totalCharges = self::ZERO;
        $totalRevenue = self::ZERO;
        $totalCommission = self::ZERO;
        $statusCounts = [];

        foreach ($rows as $row) {
            $totalSubtotal = $this->math->add($totalSubtotal, (string) $row['subtotal']);
            $totalDiscount = $this->math->add($totalDiscount, (string) $row['discount_total']);
            $totalTax = $this->math->add($totalTax, (string) $row['tax_total']);
            $totalCharges = $this->math->add($totalCharges, (string) $row['charge_total']);
            $totalRevenue = $this->math->add($totalRevenue, (string) $row['grand_total']);
            $totalCommission = $this->math->add($totalCommission, (string) $row['commission_cost_total']);

            $st = (string) $row['status'];
            $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
        }

        $avgValue = $totalJobs > 0
            ? $this->math->div($totalRevenue, (string) $totalJobs)
            : self::ZERO;

        return [
            'total_jobs' => $totalJobs,
            'total_subtotal' => $totalSubtotal,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_charges' => $totalCharges,
            'total_revenue' => $totalRevenue,
            'total_commission' => $totalCommission,
            'average_job_value' => $avgValue,
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function calculateGroups(Collection $rows, string $globalTotalRevenue, int $globalTotalJobs): array
    {
        /** @var Collection<string, Collection<int, array<string, mixed>>> $grouped */
        $grouped = $rows->groupBy('job_type_code');

        $groups = [];

        foreach ($grouped as $typeCode => $groupRows) {
            $first = $groupRows->first();
            $count = $groupRows->count();

            $subtotal = self::ZERO;
            $discount = self::ZERO;
            $tax = self::ZERO;
            $charges = self::ZERO;
            $revenue = self::ZERO;

            foreach ($groupRows as $row) {
                $subtotal = $this->math->add($subtotal, (string) $row['subtotal']);
                $discount = $this->math->add($discount, (string) $row['discount_total']);
                $tax = $this->math->add($tax, (string) $row['tax_total']);
                $charges = $this->math->add($charges, (string) $row['charge_total']);
                $revenue = $this->math->add($revenue, (string) $row['grand_total']);
            }

            $avgValue = $count > 0 ? $this->math->div($revenue, (string) $count) : self::ZERO;

            $revPercentage = (! $this->math->isZero($globalTotalRevenue))
                ? round(((float) $revenue / (float) $globalTotalRevenue) * 100, 1)
                : 0.0;

            $jobsPercentage = $globalTotalJobs > 0
                ? round(($count / $globalTotalJobs) * 100, 1)
                : 0.0;

            $groups[] = [
                'job_type_id' => $first['job_type_id'],
                'job_type_code' => $typeCode,
                'job_type_name' => $first['job_type_name'],
                'tracks_mileage' => (bool) $first['tracks_mileage'],
                'total_jobs' => $count,
                'total_subtotal' => $subtotal,
                'total_discount' => $discount,
                'total_tax' => $tax,
                'total_charges' => $charges,
                'total_revenue' => $revenue,
                'average_job_value' => $avgValue,
                'revenue_share_percentage' => $revPercentage,
                'jobs_share_percentage' => $jobsPercentage,
                'jobs' => $groupRows->values()->all(),
            ];
        }

        // Sort groups by revenue descending
        usort($groups, function (array $a, array $b): int {
            $cmp = $this->math->compare((string) $b['total_revenue'], (string) $a['total_revenue']);

            return $cmp !== 0 ? $cmp : strcmp((string) $a['job_type_name'], (string) $b['job_type_name']);
        });

        return $groups;
    }
}
