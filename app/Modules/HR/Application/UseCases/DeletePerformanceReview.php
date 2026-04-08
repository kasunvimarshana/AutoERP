<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\PerformanceReviewDeleted;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class DeletePerformanceReview
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $review = $this->repo->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $tenantId = $review->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            PerformanceReviewDeleted::dispatch($tenantId, $id);
        }

        return $deleted;
    }
}
