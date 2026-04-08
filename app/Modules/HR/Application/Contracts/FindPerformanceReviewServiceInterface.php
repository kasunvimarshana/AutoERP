<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindPerformanceReviewServiceInterface extends ReadServiceInterface
{
    /**
     * @return array<int, \Modules\HR\Domain\Entities\PerformanceReview>
     */
    public function getByEmployee(int $employeeId): array;

    /**
     * @return array<int, \Modules\HR\Domain\Entities\PerformanceReview>
     */
    public function getByReviewer(int $reviewerId): array;
}
