<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\DTOs\PerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class CreatePerformanceReviewService extends BaseService implements CreatePerformanceReviewServiceInterface
{
    public function __construct(
        private readonly PerformanceReviewRepositoryInterface $reviewRepository,
    ) {
        parent::__construct($this->reviewRepository);
    }

    protected function handle(array $data): PerformanceReview
    {
        $dto = PerformanceReviewData::fromArray($data);

        $now = new \DateTimeImmutable;
        $review = new PerformanceReview(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            cycleId: $dto->cycleId,
            reviewerId: $dto->reviewerId,
            overallRating: null,
            goals: $dto->goals,
            strengths: $dto->strengths,
            improvements: $dto->improvements,
            reviewerComments: $dto->reviewerComments,
            employeeComments: $dto->employeeComments,
            status: 'draft',
            acknowledgedAt: null,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->reviewRepository->save($review);
    }
}
