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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Contracts\TimezoneResolverInterface;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class UpcomingServicesReportService
{
    public function __construct(
        private readonly ?TimezoneResolverInterface $timezoneResolver = null,
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

        $todayCarbon = $this->timezoneResolver !== null
            ? $this->timezoneResolver->today($tenantId, $organizationUnitId)
            : CarbonImmutable::now()->startOfDay();

        $scopedQuery = $this->scopedBaseQuery($params, $tenantId, $organizationUnitId);
        $summary = $this->calculateSummary(clone $scopedQuery, $todayCarbon);

        $filteredQuery = $this->applyDateAndPresetFilters($scopedQuery, $params, $todayCarbon);

        $filteredQuery->orderBy('vehicle_service_jobs.next_service_date', 'asc')
            ->orderByDesc('vehicle_service_jobs.job_date')
            ->orderByDesc('vehicle_service_jobs.id');

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));

        /** @var LengthAwarePaginator $paginator */
        $paginator = $filteredQuery
            ->with(['vehicle.make', 'vehicle.model', 'customer'])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $this->hydrateRows(collect($paginator->items()), $todayCarbon),
            'summary' => $summary,
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
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = isset($params['organization_unit_id']) && is_numeric($params['organization_unit_id'])
            ? (int) $params['organization_unit_id']
            : null;

        $query = $this->applyDateAndPresetFilters(
            $this->scopedBaseQuery($params, $tenantId, $organizationUnitId),
            $params
        )->orderBy('vehicle_service_jobs.next_service_date', 'asc')
            ->orderByDesc('vehicle_service_jobs.job_date')
            ->orderByDesc('vehicle_service_jobs.id');

        $count = (clone $query)->count();
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ($count > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$count} rows. Narrow the filters to {$limit} rows or fewer before exporting."],
            ]);
        }

        return $this->hydrateRows($query->with(['vehicle.make', 'vehicle.model', 'customer'])->get());
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/upcoming-services',
            title: 'Upcoming Vehicle Services',
            group: 'Vehicle Service',
            model: VehicleServiceJob::class,
            columns: [
                new ReportColumn('next_service_date', 'Next service date', format: 'date'),
                new ReportColumn('urgency_label', 'Status / Urgency'),
                new ReportColumn('registration_number', 'Registration no.'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('customer_phone', 'Phone'),
                new ReportColumn('job_number', 'Last job no.'),
                new ReportColumn('job_date', 'Last service date', format: 'date'),
                new ReportColumn('job_type_label', 'Service type'),
                new ReportColumn('odometer_reading', 'Last odometer', format: 'decimal'),
                new ReportColumn('next_service_mileage', 'Next service mileage', format: 'decimal'),
            ],
            dateColumn: 'next_service_date',
            defaultSort: 'next_service_date',
            defaultDirection: 'asc',
            description: 'Vehicles due or overdue for scheduled service based on next service date.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function scopedBaseQuery(array $params, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = VehicleServiceJob::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('vehicle_service_jobs.status', '<>', VehicleServiceJobStatus::Cancelled->value)
            ->whereNotNull('vehicle_service_jobs.next_service_date');

        $latestOnly = filter_var($params['latest_only'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if ($latestOnly) {
            $query->whereNotExists(function ($subQuery) use ($organizationUnitId): void {
                $subQuery->select(DB::raw(1))
                    ->from('vehicle_service_jobs as newer_jobs')
                    ->whereColumn('newer_jobs.vehicle_id', 'vehicle_service_jobs.vehicle_id')
                    ->whereColumn('newer_jobs.tenant_id', 'vehicle_service_jobs.tenant_id')
                    ->where('newer_jobs.status', '<>', VehicleServiceJobStatus::Cancelled->value);

                if ($organizationUnitId !== null) {
                    $subQuery->where('newer_jobs.organization_unit_id', $organizationUnitId);
                } else {
                    $subQuery->whereNull('newer_jobs.organization_unit_id');
                }

                $subQuery->where(function ($dateQuery): void {
                    $dateQuery->where(function ($q): void {
                        $q->whereNotNull('newer_jobs.next_service_date')
                            ->where(function ($tieBreaker): void {
                                $tieBreaker->whereColumn('newer_jobs.job_date', '>', 'vehicle_service_jobs.job_date')
                                    ->orWhere(function ($idTie): void {
                                        $idTie->whereColumn('newer_jobs.job_date', '=', 'vehicle_service_jobs.job_date')
                                            ->whereColumn('newer_jobs.id', '>', 'vehicle_service_jobs.id');
                                    });
                            });
                    })->orWhere(function ($q): void {
                        $q->whereColumn('newer_jobs.job_date', '>=', 'vehicle_service_jobs.next_service_date')
                            ->whereColumn('newer_jobs.id', '<>', 'vehicle_service_jobs.id');
                    });
                });
            });
        }

        if (! empty($params['vehicle_id'])) {
            $query->where('vehicle_service_jobs.vehicle_id', (int) $params['vehicle_id']);
        }

        if (! empty($params['customer_id'])) {
            $query->where('vehicle_service_jobs.customer_id', (int) $params['customer_id']);
        }

        if (! empty($params['job_status'])) {
            $query->where('vehicle_service_jobs.status', $params['job_status']);
        }

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
     * @return array<string, int>
     */
    private function calculateSummary(Builder $query, ?CarbonImmutable $todayCarbon = null): array
    {
        $todayCarbon ??= CarbonImmutable::now()->startOfDay();
        $today = $todayCarbon->toDateString();
        $weekEnd = $todayCarbon->addDays(7)->toDateString();
        $monthEnd = $todayCarbon->addDays(30)->toDateString();

        return [
            'total_upcoming' => (clone $query)->count(),
            'total_overdue' => (clone $query)->whereDate('vehicle_service_jobs.next_service_date', '<', $today)->count(),
            'total_due_today' => (clone $query)->whereDate('vehicle_service_jobs.next_service_date', '=', $today)->count(),
            'total_due_this_week' => (clone $query)
                ->whereDate('vehicle_service_jobs.next_service_date', '>', $today)
                ->whereDate('vehicle_service_jobs.next_service_date', '<=', $weekEnd)
                ->count(),
            'total_due_this_month' => (clone $query)
                ->whereDate('vehicle_service_jobs.next_service_date', '>', $weekEnd)
                ->whereDate('vehicle_service_jobs.next_service_date', '<=', $monthEnd)
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function applyDateAndPresetFilters(Builder $query, array $params, ?CarbonImmutable $todayCarbon = null): Builder
    {
        $todayCarbon ??= CarbonImmutable::now()->startOfDay();
        $today = $todayCarbon->toDateString();
        $weekEnd = $todayCarbon->addDays(7)->toDateString();
        $monthEnd = $todayCarbon->addDays(30)->toDateString();

        $preset = (string) ($params['status_preset'] ?? 'all');
        match ($preset) {
            'overdue' => $query->whereDate('vehicle_service_jobs.next_service_date', '<', $today),
            'due_today' => $query->whereDate('vehicle_service_jobs.next_service_date', '=', $today),
            'due_this_week' => $query->whereDate('vehicle_service_jobs.next_service_date', '>', $today)
                ->whereDate('vehicle_service_jobs.next_service_date', '<=', $weekEnd),
            'due_this_month' => $query->whereDate('vehicle_service_jobs.next_service_date', '>', $weekEnd)
                ->whereDate('vehicle_service_jobs.next_service_date', '<=', $monthEnd),
            'upcoming' => $query->whereDate('vehicle_service_jobs.next_service_date', '>=', $today),
            default => null,
        };

        if (! empty($params['date_from'])) {
            $query->whereDate('vehicle_service_jobs.next_service_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('vehicle_service_jobs.next_service_date', '<=', $params['date_to']);
        }

        return $query;
    }

    /**
     * @param  Collection<int, VehicleServiceJob>  $jobs
     * @return Collection<int, array<string, mixed>>
     */
    private function hydrateRows(Collection $jobs, ?CarbonImmutable $todayCarbon = null): Collection
    {
        $todayCarbon ??= CarbonImmutable::now()->startOfDay();

        return $jobs->map(function (VehicleServiceJob $job) use ($todayCarbon): array {
            $nextServiceDate = $job->next_service_date?->toDateString();
            $jobDate = $job->job_date?->toDateString();
            $nextDateCarbon = $job->next_service_date ? CarbonImmutable::parse($job->next_service_date)->startOfDay() : null;

            $daysDiff = $nextDateCarbon ? (int) $todayCarbon->diffInDays($nextDateCarbon, false) : 0;

            if ($daysDiff < 0) {
                $urgencyStatus = 'overdue';
                $daysOverdue = abs($daysDiff);
                $daysUntilDue = 0;
                $urgencyLabel = $daysOverdue === 1 ? '1 day overdue' : "{$daysOverdue} days overdue";
            } elseif ($daysDiff === 0) {
                $urgencyStatus = 'due_today';
                $daysOverdue = 0;
                $daysUntilDue = 0;
                $urgencyLabel = 'Due today';
            } elseif ($daysDiff <= 7) {
                $urgencyStatus = 'due_this_week';
                $daysOverdue = 0;
                $daysUntilDue = $daysDiff;
                $urgencyLabel = $daysDiff === 1 ? 'Due tomorrow' : "Due in {$daysDiff} days";
            } elseif ($daysDiff <= 30) {
                $urgencyStatus = 'due_this_month';
                $daysOverdue = 0;
                $daysUntilDue = $daysDiff;
                $urgencyLabel = "Due in {$daysDiff} days";
            } else {
                $urgencyStatus = 'upcoming';
                $daysOverdue = 0;
                $daysUntilDue = $daysDiff;
                $urgencyLabel = "Due in {$daysDiff} days";
            }

            $vehicle = $job->vehicle;
            $vehicleMake = $vehicle?->make?->name;
            $vehicleModel = $vehicle?->model?->name;
            $vehicleMakeModel = trim(($vehicleMake ? $vehicleMake.' ' : '').($vehicleModel ?? ''));
            $registrationNumber = (string) ($vehicle?->registration_number ?: $vehicle?->vehicle_number ?: 'Not specified');
            $vehicleLabel = $vehicleMakeModel !== '' ? "{$registrationNumber} ({$vehicleMakeModel})" : $registrationNumber;

            $customer = $job->customer;
            $customerName = (string) ($customer?->display_name ?? $customer?->name ?? 'Not specified');
            $customerPhone = (string) ($customer?->phone ?? '');
            $customerEmail = (string) ($customer?->email ?? '');
            $customerCode = (string) ($customer?->customer_number ?? $customer?->code ?? '');

            $jobType = $job->type instanceof \BackedEnum ? $job->type->value : (string) $job->type;
            $jobStatus = $job->status instanceof \BackedEnum ? $job->status->value : (string) $job->status;

            return [
                'id' => (int) $job->getKey(),
                'job_number' => (string) $job->job_number,
                'job_date' => $jobDate,
                'job_type' => $jobType,
                'job_type_label' => $this->humanize($jobType),
                'job_status' => $jobStatus,
                'job_status_label' => $this->humanize($jobStatus),
                'next_service_date' => $nextServiceDate,
                'days_diff' => $daysDiff,
                'days_overdue' => $daysOverdue,
                'days_until_due' => $daysUntilDue,
                'urgency_status' => $urgencyStatus,
                'urgency_label' => $urgencyLabel,
                'odometer_reading' => $job->odometer_reading === null ? null : (string) $job->odometer_reading,
                'next_service_mileage' => $job->next_service_mileage === null ? null : (string) $job->next_service_mileage,
                'vehicle' => [
                    'id' => $vehicle ? (int) $vehicle->getKey() : (int) $job->vehicle_id,
                    'registration_number' => $registrationNumber,
                    'vehicle_number' => $vehicle?->vehicle_number,
                    'display_name' => $vehicleLabel,
                    'make' => $vehicleMake,
                    'model' => $vehicleModel,
                    'year' => $vehicle?->manufacture_year,
                    'current_odometer' => $vehicle?->odometer_reading === null ? null : (string) $vehicle->odometer_reading,
                    'odometer_unit' => $vehicle?->odometer_unit,
                ],
                'registration_number' => $registrationNumber,
                'vehicle_label' => $vehicleLabel,
                'customer' => [
                    'id' => $customer ? (int) $customer->getKey() : (int) $job->customer_id,
                    'code' => $customerCode,
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'email' => $customerEmail,
                ],
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
            ];
        })->values();
    }

    private function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
