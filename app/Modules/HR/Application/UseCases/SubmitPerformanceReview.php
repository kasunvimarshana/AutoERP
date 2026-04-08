<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Events\PerformanceReviewSubmitted;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class SubmitPerformanceReview
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(int $id): PerformanceReview
    {
        $review = $this->repo->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $review->submit();

        $saved = $this->repo->save($review);
        PerformanceReviewSubmitted::dispatch($saved);

        return $saved;
    }
}
