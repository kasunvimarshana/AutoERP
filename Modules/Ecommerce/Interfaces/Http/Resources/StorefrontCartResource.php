<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;

class StorefrontCartResource extends JsonResource
{
    /** @var StorefrontCart */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'user_id' => $this->resource->userId,
            'token' => $this->resource->token,
            'status' => $this->resource->status,
            'currency' => $this->resource->currency,
            'subtotal' => $this->resource->subtotal,
            'tax_amount' => $this->resource->taxAmount,
            'total_amount' => $this->resource->totalAmount,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
