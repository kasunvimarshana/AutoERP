<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PerformanceReviews;

use Modules\Core\Application\Results\Result;

interface UpdatePerformanceReviewServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}