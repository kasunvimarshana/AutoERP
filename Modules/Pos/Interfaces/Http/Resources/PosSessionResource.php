<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pos\Domain\Entities\PosSession;

class PosSessionResource extends JsonResource
{
    /** @var PosSession */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'user_id' => $this->resource->userId,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'opened_at' => $this->resource->openedAt,
            'closed_at' => $this->resource->closedAt,
            'currency' => $this->resource->currency,
            'opening_float' => $this->resource->openingFloat,
            'closing_float' => $this->resource->closingFloat,
            'total_sales' => $this->resource->totalSales,
            'total_refunds' => $this->resource->totalRefunds,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
