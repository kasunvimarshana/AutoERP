<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class GetPerformanceReview
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(int $id): ?PerformanceReview
    {
        return $this->repo->find($id);
    }
}
