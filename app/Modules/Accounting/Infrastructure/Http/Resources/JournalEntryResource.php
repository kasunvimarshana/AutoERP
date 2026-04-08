<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class JournalEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->resource->id,
            'tenant_id'   => $this->resource->tenantId,
            'number'      => $this->resource->number,
            'date'        => $this->resource->date->format('Y-m-d'),
            'description' => $this->resource->description,
            'reference'   => $this->resource->reference,
            'status'      => $this->resource->status,
            'source_type' => $this->resource->sourceType,
            'source_id'   => $this->resource->sourceId,
            'posted_at'   => $this->resource->postedAt?->format('Y-m-d H:i:s'),
            'voided_at'   => $this->resource->voidedAt?->format('Y-m-d H:i:s'),
            'created_at'  => $this->resource->createdAt,
            'updated_at'  => $this->resource->updatedAt,
        ];
    }
}
