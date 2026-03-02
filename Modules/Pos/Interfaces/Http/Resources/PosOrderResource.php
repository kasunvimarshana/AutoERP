<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pos\Domain\Entities\PosOrder;

class PosOrderResource extends JsonResource
{
    /** @var PosOrder */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'pos_session_id' => $this->resource->posSessionId,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'currency' => $this->resource->currency,
            'subtotal' => $this->resource->subtotal,
            'tax_amount' => $this->resource->taxAmount,
            'discount_amount' => $this->resource->discountAmount,
            'total_amount' => $this->resource->totalAmount,
            'paid_amount' => $this->resource->paidAmount,
            'change_amount' => $this->resource->changeAmount,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
