<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\DTOs\PerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Events\PerformanceReviewCreated;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class CreatePerformanceReview
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(PerformanceReviewData $data): PerformanceReview
    {
        $review = new PerformanceReview(
            tenantId:          $data->tenant_id,
            employeeId:        $data->employee_id,
            reviewerId:        $data->reviewer_id,
            reviewPeriodStart: $data->review_period_start,
            reviewPeriodEnd:   $data->review_period_end,
            rating:            $data->rating,
            comments:          $data->comments,
            goals:             $data->goals,
            achievements:      $data->achievements,
            status:            $data->status ?? 'draft',
            metadata:          $data->metadata !== null ? new Metadata($data->metadata) : null,
        );

        $saved = $this->repo->save($review);
        PerformanceReviewCreated::dispatch($saved);

        return $saved;
    }
}
