<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Application\DTOs\UpdatePerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Events\PerformanceReviewUpdated;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class UpdatePerformanceReview
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(UpdatePerformanceReviewData $data): PerformanceReview
    {
        $id     = (int) ($data->id ?? 0);
        $review = $this->repo->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $reviewPeriodStart = $data->isProvided('review_period_start') ? (string) $data->review_period_start : $review->getReviewPeriodStart();
        $reviewPeriodEnd   = $data->isProvided('review_period_end') ? (string) $data->review_period_end : $review->getReviewPeriodEnd();
        $rating            = $data->isProvided('rating') ? (float) $data->rating : $review->getRating();
        $comments          = $data->isProvided('comments') ? $data->comments : $review->getComments();
        $goals             = $data->isProvided('goals') ? $data->goals : $review->getGoals();
        $achievements      = $data->isProvided('achievements') ? $data->achievements : $review->getAchievements();

        $review->updateDetails($reviewPeriodStart, $reviewPeriodEnd, $rating, $comments, $goals, $achievements);

        $saved = $this->repo->save($review);
        PerformanceReviewUpdated::dispatch($saved);

        return $saved;
    }
}
