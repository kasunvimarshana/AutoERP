<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PerformanceReview;

interface PerformanceReviewRepositoryInterface extends RepositoryInterface
{
    public function save(PerformanceReview $review): PerformanceReview;

    public function find(int|string $id, array $columns = ['*']): ?PerformanceReview;

    public function findByEmployeeAndCycle(int $tenantId, int $employeeId, int $cycleId): ?PerformanceReview;
}
