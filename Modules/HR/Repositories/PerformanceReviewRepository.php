<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\PerformanceReview;
use Modules\HR\Repositories\Contracts\PerformanceReviewRepositoryInterface;

/**
 * PerformanceReview Repository Implementation
 *
 * Handles all performance review data access operations.
 */
class PerformanceReviewRepository extends BaseRepository implements PerformanceReviewRepositoryInterface
{
    /**
     * PerformanceReviewRepository constructor.
     */
    public function __construct(PerformanceReview $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('employee_id', $employeeId)->orderBy('review_period_start', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByReviewer(int $reviewerId): Collection
    {
        return $this->model->where('reviewer_id', $reviewerId)->orderBy('review_period_start', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByPeriod(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where('review_period_start', '>=', $startDate)
            ->where('review_period_end', '<=', $endDate)
            ->get();
    }
}
