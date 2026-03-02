<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pos\Domain\Entities\PosPayment;

class PosPaymentResource extends JsonResource
{
    /** @var PosPayment */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'pos_order_id' => $this->resource->posOrderId,
            'method' => $this->resource->method,
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
            'reference' => $this->resource->reference,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
