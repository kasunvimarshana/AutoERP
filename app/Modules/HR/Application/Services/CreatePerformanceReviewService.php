<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\DTOs\PerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Events\PerformanceReviewCreated;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class CreatePerformanceReviewService extends BaseService implements CreatePerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $performanceReviewRepository)
    {
        parent::__construct($performanceReviewRepository);
    }

    protected function handle(array $data): PerformanceReview
    {
        $dto = PerformanceReviewData::fromArray($data);

        $review = new PerformanceReview(
            tenantId:          $dto->tenant_id,
            employeeId:        $dto->employee_id,
            reviewerId:        $dto->reviewer_id,
            reviewPeriodStart: $dto->review_period_start,
            reviewPeriodEnd:   $dto->review_period_end,
            rating:            $dto->rating,
            comments:          $dto->comments,
            goals:             $dto->goals,
            achievements:      $dto->achievements,
            status:            $dto->status ?? 'draft',
            metadata:          $dto->metadata !== null ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->performanceReviewRepository->save($review);
        $this->addEvent(new PerformanceReviewCreated($saved));

        return $saved;
    }
}
