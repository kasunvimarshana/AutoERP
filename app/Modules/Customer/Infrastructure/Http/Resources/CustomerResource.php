<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->resource->id,
            'tenant_id'    => $this->resource->tenantId,
            'name'         => $this->resource->name,
            'code'         => $this->resource->code,
            'email'        => $this->resource->email,
            'phone'        => $this->resource->phone,
            'address'      => $this->resource->address,
            'tax_number'   => $this->resource->taxNumber,
            'currency'     => $this->resource->currency,
            'credit_limit' => $this->resource->creditLimit,
            'balance'      => $this->resource->balance,
            'is_active'    => $this->resource->isActive,
            'created_at'   => $this->resource->createdAt,
            'updated_at'   => $this->resource->updatedAt,
        ];
    }
}
