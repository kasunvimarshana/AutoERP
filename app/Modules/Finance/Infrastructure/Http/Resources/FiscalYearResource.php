<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalYearResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'name' => $this->resource->getName(),
            'start_date' => $this->resource->getStartDate()->format('Y-m-d'),
            'end_date' => $this->resource->getEndDate()->format('Y-m-d'),
            'status' => $this->resource->getStatus(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
