<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_unit_id' => $this->resource->organization_unit_id,
            'customer_code' => $this->resource->customer_code,
            'name' => $this->resource->customer_name,
            'display_name' => $this->resource->display_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'mobile' => $this->resource->mobile,
            'credit_limit' => $this->resource->credit_limit,
            'payment_terms_days' => $this->resource->payment_terms_days,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
