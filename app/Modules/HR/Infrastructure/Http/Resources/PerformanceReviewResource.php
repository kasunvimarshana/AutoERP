<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PerformanceReview;

class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PerformanceReview $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'cycle_id' => $entity->getCycleId(),
            'reviewer_id' => $entity->getReviewerId(),
            'overall_rating' => $entity->getOverallRating()?->value,
            'goals' => $entity->getGoals(),
            'strengths' => $entity->getStrengths(),
            'improvements' => $entity->getImprovements(),
            'reviewer_comments' => $entity->getReviewerComments(),
            'employee_comments' => $entity->getEmployeeComments(),
            'status' => $entity->getStatus(),
            'acknowledged_at' => $entity->getAcknowledgedAt()?->format('c'),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
