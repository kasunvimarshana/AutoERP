<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\DTOs\UpdatePerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Events\PerformanceReviewUpdated;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class UpdatePerformanceReviewService extends BaseService implements UpdatePerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $performanceReviewRepository)
    {
        parent::__construct($performanceReviewRepository);
    }

    protected function handle(array $data): PerformanceReview
    {
        $dto    = UpdatePerformanceReviewData::fromArray($data);
        $id     = (int) ($dto->id ?? 0);
        $review = $this->performanceReviewRepository->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $reviewPeriodStart = $dto->isProvided('review_period_start') ? (string) $dto->review_period_start : $review->getReviewPeriodStart();
        $reviewPeriodEnd   = $dto->isProvided('review_period_end') ? (string) $dto->review_period_end : $review->getReviewPeriodEnd();
        $rating            = $dto->isProvided('rating') ? (float) $dto->rating : $review->getRating();
        $comments          = $dto->isProvided('comments') ? $dto->comments : $review->getComments();
        $goals             = $dto->isProvided('goals') ? $dto->goals : $review->getGoals();
        $achievements      = $dto->isProvided('achievements') ? $dto->achievements : $review->getAchievements();

        $review->updateDetails($reviewPeriodStart, $reviewPeriodEnd, $rating, $comments, $goals, $achievements);

        $saved = $this->performanceReviewRepository->save($review);
        $this->addEvent(new PerformanceReviewUpdated($saved));

        return $saved;
    }
}
