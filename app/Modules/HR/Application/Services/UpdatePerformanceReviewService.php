<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\DTOs\PerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PerformanceRating;

class UpdatePerformanceReviewService extends BaseService implements UpdatePerformanceReviewServiceInterface
{
    public function __construct(
        private readonly PerformanceReviewRepositoryInterface $reviewRepository,
    ) {
        parent::__construct($this->reviewRepository);
    }

    protected function handle(array $data): PerformanceReview
    {
        $id = (int) ($data['id'] ?? 0);
        $review = $this->reviewRepository->find($id);

        if ($review === null) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $dto = PerformanceReviewData::fromArray($data);

        if ($review->getTenantId() !== $dto->tenantId) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $overallRating = isset($data['overall_rating'])
            ? PerformanceRating::from((string) $data['overall_rating'])
            : $review->getOverallRating();

        $updated = new PerformanceReview(
            tenantId: $review->getTenantId(),
            employeeId: $review->getEmployeeId(),
            cycleId: $review->getCycleId(),
            reviewerId: $review->getReviewerId(),
            overallRating: $overallRating,
            goals: $dto->goals,
            strengths: $dto->strengths,
            improvements: $dto->improvements,
            reviewerComments: $dto->reviewerComments,
            employeeComments: $dto->employeeComments,
            status: $review->getStatus(),
            acknowledgedAt: $review->getAcknowledgedAt(),
            metadata: $dto->metadata,
            createdAt: $review->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $review->getId(),
        );

        return $this->reviewRepository->save($updated);
    }
}
