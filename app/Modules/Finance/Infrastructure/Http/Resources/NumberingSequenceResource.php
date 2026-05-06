<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NumberingSequenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'module' => $this->resource->getModule(),
            'document_type' => $this->resource->getDocumentType(),
            'prefix' => $this->resource->getPrefix(),
            'suffix' => $this->resource->getSuffix(),
            'next_number' => $this->resource->getNextNumber(),
            'padding' => $this->resource->getPadding(),
            'is_active' => $this->resource->isActive(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
