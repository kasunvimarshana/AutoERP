<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\PerformanceReview;

/**
 * PerformanceReview Repository Interface
 *
 * Defines the contract for performance review data access operations.
 */
interface PerformanceReviewRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get performance reviews by employee.
     */
    public function getByEmployee(int $employeeId): Collection;

    /**
     * Get performance reviews by reviewer.
     */
    public function getByReviewer(int $reviewerId): Collection;

    /**
     * Get performance reviews by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get performance reviews by period.
     */
    public function getByPeriod(string $startDate, string $endDate): Collection;
}
