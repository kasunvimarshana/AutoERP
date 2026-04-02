<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PerformanceReview;

interface PerformanceReviewRepositoryInterface extends RepositoryInterface
{
    public function save(PerformanceReview $performanceReview): PerformanceReview;

    /**
     * Return all performance reviews for a given employee.
     *
     * @return array<int, PerformanceReview>
     */
    public function getByEmployee(int $employeeId): array;

    /**
     * Return all performance reviews created by a given reviewer.
     *
     * @return array<int, PerformanceReview>
     */
    public function getByReviewer(int $reviewerId): array;
}
