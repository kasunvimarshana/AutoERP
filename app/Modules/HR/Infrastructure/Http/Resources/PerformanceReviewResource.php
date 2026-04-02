<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->getId(),
            'tenant_id'           => $this->getTenantId(),
            'employee_id'         => $this->getEmployeeId(),
            'reviewer_id'         => $this->getReviewerId(),
            'review_period_start' => $this->getReviewPeriodStart(),
            'review_period_end'   => $this->getReviewPeriodEnd(),
            'rating'              => $this->getRating(),
            'comments'            => $this->getComments(),
            'goals'               => $this->getGoals(),
            'achievements'        => $this->getAchievements(),
            'status'              => $this->getStatus(),
            'metadata'            => $this->getMetadata()->toArray(),
            'created_at'          => $this->getCreatedAt()?->format('c'),
            'updated_at'          => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
