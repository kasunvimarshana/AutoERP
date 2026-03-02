<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;

class StorefrontOrderResource extends JsonResource
{
    /** @var StorefrontOrder */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'user_id' => $this->resource->userId,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'currency' => $this->resource->currency,
            'subtotal' => $this->resource->subtotal,
            'tax_amount' => $this->resource->taxAmount,
            'shipping_amount' => $this->resource->shippingAmount,
            'discount_amount' => $this->resource->discountAmount,
            'total_amount' => $this->resource->totalAmount,
            'billing_name' => $this->resource->billingName,
            'billing_email' => $this->resource->billingEmail,
            'billing_phone' => $this->resource->billingPhone,
            'shipping_address' => $this->resource->shippingAddress,
            'notes' => $this->resource->notes,
            'cart_token' => $this->resource->cartToken,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
