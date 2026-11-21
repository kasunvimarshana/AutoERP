<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceJobType as VehicleServiceJobTypeEnum;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobFeedback;
use Modules\VehicleService\Models\VehicleServiceJobType;

final class ServiceJobFeedbackReportService
{
    private const ZERO = '0.00';

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

        // Fetch all matching feedback items with related job, customer, vehicle, job type, and user
        $allFeedbacks = (clone $scopedQuery)
            ->with([
                'job' => function ($q): void {
                    $q->with([
                        'jobType',
                        'customer',
                        'vehicle.make',
                        'vehicle.model',
                        'supervisor',
                    ]);
                },
                'user',
                'creator',
            ])
            ->orderBy('vehicle_service_job_feedbacks.created_at', 'desc')
            ->orderBy('vehicle_service_job_feedbacks.id', 'desc')
            ->get();

        $hydratedRows = $this->hydrateFeedbackRows($allFeedbacks);

        $summary = $this->calculateSummary($hydratedRows);

        // Support pagination
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));
        $totalCount = $hydratedRows->count();
        $slicedData = $hydratedRows->slice(($page - 1) * $perPage, $perPage)->values();

        $lastPage = max(1, (int) ceil($totalCount / $perPage));

        return [
            'data' => $slicedData,
            'summary' => $summary,
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

        $feedbacks = $query
            ->with([
                'job' => function ($q): void {
                    $q->with([
                        'jobType',
                        'customer',
                        'vehicle.make',
                        'vehicle.model',
                        'supervisor',
                    ]);
                },
                'user',
                'creator',
            ])
            ->orderBy('vehicle_service_job_feedbacks.created_at', 'desc')
            ->orderBy('vehicle_service_job_feedbacks.id', 'desc')
            ->get();

        $rows = $this->hydrateFeedbackRows($feedbacks);

        return $rows->map(fn (array $row): array => [
            'job_number' => $row['job_number'],
            'job_date' => $row['job_date'],
            'customer_name' => $row['customer_name'],
            'customer_phone' => $row['customer_phone'] ?? '',
            'registration_number' => $row['registration_number'],
            'vehicle_label' => $row['vehicle_label'],
            'job_type_name' => $row['job_type_name'],
            'job_status_label' => $row['job_status_label'],
            'feedback_type_label' => $row['feedback_type_label'],
            'rating' => $row['rating'] !== null ? "{$row['rating']} / 5" : 'Unrated',
            'title' => $row['title'] ?? '',
            'comment' => $row['comment'],
            'author_name' => $row['author_name'] ?? 'Anonymous',
            'is_customer_visible' => $row['is_customer_visible'] ? 'Yes' : 'No',
            'created_at' => $row['created_at'],
        ]);
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/job-feedback',
            title: 'Service Jobs Reviews & Ratings Report',
            group: 'Vehicle Service',
            model: VehicleServiceJobFeedback::class,
            columns: [
                new ReportColumn('job_number', 'Job number'),
                new ReportColumn('job_date', 'Job date', format: 'date'),
                new ReportColumn('customer_name', 'Customer'),
                new ReportColumn('customer_phone', 'Phone'),
                new ReportColumn('registration_number', 'Reg no.'),
                new ReportColumn('vehicle_label', 'Vehicle'),
                new ReportColumn('job_type_name', 'Job type'),
                new ReportColumn('job_status_label', 'Job status'),
                new ReportColumn('feedback_type_label', 'Category'),
                new ReportColumn('rating', 'Rating'),
                new ReportColumn('title', 'Title'),
                new ReportColumn('comment', 'Comment'),
                new ReportColumn('author_name', 'Author'),
                new ReportColumn('is_customer_visible', 'Customer visible'),
                new ReportColumn('created_at', 'Submitted at', format: 'datetime'),
            ],
            dateColumn: 'created_at',
            defaultSort: 'created_at',
            defaultDirection: 'desc',
            description: 'Comprehensive report covering service jobs, customer reviews, internal notes, feedback comments, and rating distributions.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildBaseQuery(array $params, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = VehicleServiceJobFeedback::query()
            ->forContext($tenantId, $organizationUnitId)
            ->join('vehicle_service_jobs', 'vehicle_service_job_feedbacks.vehicle_service_job_id', '=', 'vehicle_service_jobs.id')
            ->select('vehicle_service_job_feedbacks.*');

        // Apply date filtering
        $this->applyDateFilters($query, $params);

        // Feedback type / category filter
        if (! empty($params['feedback_type']) && $params['feedback_type'] !== 'all') {
            $query->where('vehicle_service_job_feedbacks.feedback_type', $params['feedback_type']);
        }

        // Rating filter
        if (isset($params['rating']) && $params['rating'] !== '' && $params['rating'] !== 'all') {
            $ratingVal = (string) $params['rating'];
            if ($ratingVal === 'rated') {
                $query->whereNotNull('vehicle_service_job_feedbacks.rating');
            } elseif ($ratingVal === 'unrated') {
                $query->whereNull('vehicle_service_job_feedbacks.rating');
            } elseif (is_numeric($ratingVal)) {
                $query->where('vehicle_service_job_feedbacks.rating', (int) $ratingVal);
            }
        }

        // Customer visibility filter
        if (isset($params['is_customer_visible']) && $params['is_customer_visible'] !== '' && $params['is_customer_visible'] !== 'all') {
            if ($params['is_customer_visible'] === 'customer_visible' || $params['is_customer_visible'] === 'true' || $params['is_customer_visible'] === true) {
                $query->where('vehicle_service_job_feedbacks.is_customer_visible', true);
            } elseif ($params['is_customer_visible'] === 'internal_only' || $params['is_customer_visible'] === 'false' || $params['is_customer_visible'] === false) {
                $query->where('vehicle_service_job_feedbacks.is_customer_visible', false);
            }
        }

        // Job status filter
        if (! empty($params['job_status']) && $params['job_status'] !== 'all') {
            $query->where('vehicle_service_jobs.status', $params['job_status']);
        }

        // Job type filter
        if (! empty($params['job_type_id'])) {
            $query->where('vehicle_service_jobs.job_type_id', (int) $params['job_type_id']);
        } elseif (! empty($params['job_type_code'])) {
            $code = (string) $params['job_type_code'];
            $query->where(function (Builder $q) use ($code): void {
                $q->whereHas('job.jobType', fn (Builder $jtq) => $jtq->where('code', $code))
                    ->orWhere('vehicle_service_jobs.type', $code);
            });
        }

        // Customer filter
        if (! empty($params['customer_id'])) {
            $query->where('vehicle_service_jobs.customer_id', (int) $params['customer_id']);
        }

        // Vehicle filter
        if (! empty($params['vehicle_id'])) {
            $query->where('vehicle_service_jobs.vehicle_id', (int) $params['vehicle_id']);
        }

        // Full-text search
        if (! empty($params['search'])) {
            $search = trim((string) $params['search']);
            $term = "%{$search}%";
            $query->where(function (Builder $q) use ($term): void {
                $q->where('vehicle_service_jobs.job_number', 'like', $term)
                    ->orWhere('vehicle_service_job_feedbacks.title', 'like', $term)
                    ->orWhere('vehicle_service_job_feedbacks.comment', 'like', $term)
                    ->orWhere('vehicle_service_job_feedbacks.author_name', 'like', $term)
                    ->orWhereHas('job.vehicle', function (Builder $vq) use ($term): void {
                        $vq->where('registration_number', 'like', $term)
                            ->orWhere('vehicle_number', 'like', $term);
                    })
                    ->orWhereHas('job.customer', function (Builder $cq) use ($term): void {
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
        $dateBy = ($params['date_by'] ?? 'job_date') === 'feedback_date'
            ? 'vehicle_service_job_feedbacks.created_at'
            : 'vehicle_service_jobs.job_date';

        $preset = (string) ($params['date_preset'] ?? '');
        $today = CarbonImmutable::now();

        if ($preset !== '') {
            match ($preset) {
                'today' => $query->whereDate($dateBy, '=', $today->toDateString()),
                'this_week' => $query->whereDate($dateBy, '>=', $today->startOfWeek()->toDateString())
                    ->whereDate($dateBy, '<=', $today->endOfWeek()->toDateString()),
                'this_month' => $query->whereDate($dateBy, '>=', $today->startOfMonth()->toDateString())
                    ->whereDate($dateBy, '<=', $today->endOfMonth()->toDateString()),
                'this_year' => $query->whereDate($dateBy, '>=', $today->startOfYear()->toDateString())
                    ->whereDate($dateBy, '<=', $today->endOfYear()->toDateString()),
                'all' => null,
                default => null,
            };
        }

        if (! empty($params['date_from'])) {
            $query->whereDate($dateBy, '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate($dateBy, '<=', $params['date_to']);
        }
    }

    /**
     * @param  Collection<int, VehicleServiceJobFeedback>  $feedbacks
     * @return Collection<int, array<string, mixed>>
     */
    private function hydrateFeedbackRows(Collection $feedbacks): Collection
    {
        $defaultTypes = VehicleServiceJobTypeEnum::defaults();

        return $feedbacks->map(function (VehicleServiceJobFeedback $feedback) use ($defaultTypes): array {
            /** @var VehicleServiceJob|null $job */
            $job = $feedback->job;

            /** @var VehicleServiceJobType|null $jobTypeModel */
            $jobTypeModel = $job?->jobType;

            if ($jobTypeModel !== null) {
                $typeId = (int) $jobTypeModel->getKey();
                $typeCode = (string) $jobTypeModel->code;
                $typeName = (string) $jobTypeModel->name;
            } elseif (! empty($job?->type)) {
                $code = (string) $job->type;
                $defaultDef = $defaultTypes[$code] ?? null;
                $typeId = null;
                $typeCode = $code;
                $typeName = $defaultDef !== null ? (string) $defaultDef['name'] : ucwords(str_replace('_', ' ', $code));
            } else {
                $typeId = null;
                $typeCode = 'uncategorized';
                $typeName = 'Uncategorized';
            }

            $customer = $job?->customer;
            $customerName = $customer?->display_name ?? $customer?->name ?? 'Unknown Customer';
            $customerPhone = $customer?->phone ?? null;

            $vehicle = $job?->vehicle;
            $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? 'Unknown Vehicle';
            $makeName = $vehicle?->make?->name ?? '';
            $modelName = $vehicle?->model?->name ?? '';
            $makeModel = trim("{$makeName} {$modelName}");
            $vehicleLabel = $makeModel !== '' ? "{$regNo} ({$makeModel})" : $regNo;

            $statusEnum = $job?->status instanceof VehicleServiceJobStatus
                ? $job->status
                : ($job !== null ? VehicleServiceJobStatus::tryFrom((string) $job->status) : null);

            $statusValue = $statusEnum?->value ?? (string) ($job?->status ?? 'unknown');
            $statusLabel = ucwords(str_replace('_', ' ', $statusValue));

            $typeEnum = $feedback->feedback_type instanceof VehicleServiceFeedbackType
                ? $feedback->feedback_type
                : VehicleServiceFeedbackType::tryFrom((string) $feedback->feedback_type);

            $feedbackTypeValue = $typeEnum?->value ?? (string) $feedback->feedback_type;
            $feedbackTypeLabel = $typeEnum?->label() ?? ucwords(str_replace('_', ' ', $feedbackTypeValue));

            $authorDisplay = $feedback->author_name ?? $feedback->user?->name ?? $feedback->creator?->name ?? 'Anonymous';

            return [
                'id' => (int) $feedback->getKey(),
                'job_id' => $job !== null ? (int) $job->getKey() : (int) $feedback->vehicle_service_job_id,
                'job_number' => (string) ($job?->job_number ?? ''),
                'job_date' => $job?->job_date?->toDateString() ?? '',
                'job_status' => $statusValue,
                'job_status_label' => $statusLabel,
                'job_type_id' => $typeId,
                'job_type_code' => $typeCode,
                'job_type_name' => $typeName,
                'customer_id' => $customer !== null ? (int) $customer->getKey() : ($job !== null ? (int) $job->customer_id : null),
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customer?->email ?? null,
                'vehicle_id' => $vehicle !== null ? (int) $vehicle->getKey() : ($job !== null ? (int) $job->vehicle_id : null),
                'registration_number' => $regNo,
                'vehicle_label' => $vehicleLabel,
                'feedback_type' => $feedbackTypeValue,
                'feedback_type_label' => $feedbackTypeLabel,
                'rating' => $feedback->rating !== null ? (int) $feedback->rating : null,
                'title' => $feedback->title,
                'comment' => (string) $feedback->comment,
                'author_name' => $authorDisplay,
                'is_customer_visible' => (bool) $feedback->is_customer_visible,
                'created_at' => $feedback->created_at?->toIso8601String() ?? '',
                'created_at_formatted' => $feedback->created_at?->format('Y-m-d H:i') ?? '',
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function calculateSummary(Collection $rows): array
    {
        $totalFeedbacks = $rows->count();
        $distinctJobIds = [];

        $reviewsCount = 0;
        $notesCount = 0;
        $customerFeedbackCount = 0;
        $complaintsCount = 0;
        $customerVisibleCount = 0;

        $ratedCount = 0;
        $ratingSum = 0;
        $ratingBreakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        foreach ($rows as $row) {
            $jobId = $row['job_id'];
            if ($jobId) {
                $distinctJobIds[$jobId] = true;
            }

            if (! empty($row['is_customer_visible'])) {
                $customerVisibleCount++;
            }

            match ($row['feedback_type']) {
                VehicleServiceFeedbackType::Review->value => $reviewsCount++,
                VehicleServiceFeedbackType::InternalNote->value => $notesCount++,
                VehicleServiceFeedbackType::CustomerFeedback->value => $customerFeedbackCount++,
                VehicleServiceFeedbackType::Complaint->value => $complaintsCount++,
                default => null,
            };

            $rating = $row['rating'];
            if ($rating !== null && $rating >= 1 && $rating <= 5) {
                $ratedCount++;
                $ratingSum += $rating;
                $ratingBreakdown[$rating] = ($ratingBreakdown[$rating] ?? 0) + 1;
            }
        }

        $averageRating = $ratedCount > 0 ? round($ratingSum / $ratedCount, 2) : null;
        $positiveRatingsCount = ($ratingBreakdown[5] ?? 0) + ($ratingBreakdown[4] ?? 0);
        $positivePercentage = $ratedCount > 0 ? round(($positiveRatingsCount / $ratedCount) * 100, 1) : 0.0;
        $complaintRatePercentage = $totalFeedbacks > 0 ? round(($complaintsCount / $totalFeedbacks) * 100, 1) : 0.0;

        // Breakdown percentages
        $breakdownDetails = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = $ratingBreakdown[$star] ?? 0;
            $percent = $ratedCount > 0 ? round(($count / $ratedCount) * 100, 1) : 0.0;
            $breakdownDetails[$star] = [
                'stars' => $star,
                'count' => $count,
                'percentage' => $percent,
            ];
        }

        return [
            'total_jobs' => count($distinctJobIds),
            'total_feedbacks' => $totalFeedbacks,
            'average_rating' => $averageRating,
            'total_rated' => $ratedCount,
            'total_unrated' => $totalFeedbacks - $ratedCount,
            'reviews_count' => $reviewsCount,
            'internal_notes_count' => $notesCount,
            'customer_feedback_count' => $customerFeedbackCount,
            'complaints_count' => $complaintsCount,
            'customer_visible_count' => $customerVisibleCount,
            'internal_only_count' => $totalFeedbacks - $customerVisibleCount,
            'positive_ratings_count' => $positiveRatingsCount,
            'positive_rating_percentage' => $positivePercentage,
            'complaint_rate_percentage' => $complaintRatePercentage,
            'rating_breakdown' => $ratingBreakdown,
            'rating_breakdown_details' => $breakdownDetails,
        ];
    }
}
