<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PerformanceReviews;

use Modules\Core\Application\Results\Result;

interface DeletePerformanceReviewServiceInterface
{
    public function execute(int|string $id): Result;
}