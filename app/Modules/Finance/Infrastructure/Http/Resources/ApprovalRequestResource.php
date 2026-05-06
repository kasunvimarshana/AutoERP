<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'workflow_config_id' => $this->resource->getWorkflowConfigId(),
            'entity_type' => $this->resource->getEntityType(),
            'entity_id' => $this->resource->getEntityId(),
            'status' => $this->resource->getStatus(),
            'current_step_order' => $this->resource->getCurrentStepOrder(),
            'requested_by_user_id' => $this->resource->getRequestedByUserId(),
            'resolved_by_user_id' => $this->resource->getResolvedByUserId(),
            'requested_at' => $this->resource->getRequestedAt()->format(\DateTimeInterface::ATOM),
            'resolved_at' => $this->resource->getResolvedAt()?->format(\DateTimeInterface::ATOM),
            'comments' => $this->resource->getComments(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
