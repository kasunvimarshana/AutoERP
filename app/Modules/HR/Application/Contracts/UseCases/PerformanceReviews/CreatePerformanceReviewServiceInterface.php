<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PerformanceReviews;

use Modules\Core\Application\Results\Result;

interface CreatePerformanceReviewServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}