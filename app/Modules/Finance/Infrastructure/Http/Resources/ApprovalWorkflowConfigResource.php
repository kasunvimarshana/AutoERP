<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalWorkflowConfigResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'module' => $this->resource->getModule(),
            'entity_type' => $this->resource->getEntityType(),
            'name' => $this->resource->getName(),
            'steps' => $this->resource->getSteps(),
            'min_amount' => $this->resource->getMinAmount(),
            'max_amount' => $this->resource->getMaxAmount(),
            'is_active' => $this->resource->isActive(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
