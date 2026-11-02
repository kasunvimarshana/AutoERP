<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Http\Resources\Concerns\FormatsCustomerResources;

final class CustomerSummaryResource extends JsonResource
{
    use FormatsCustomerResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'customer_number' => $this->customer_number,
            'code' => $this->code,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'customer_type' => $this->enumValue($this->customer_type),
            'status' => $this->enumValue($this->status),
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'default_currency' => $this->relationLoaded('defaultCurrency')
                ? $this->namedResource($this->defaultCurrency, true)
                : null,
            'categories' => $this->whenLoaded('categories', fn () => CustomerCategoryResource::collection($this->categories)->resolve($request)),
            'is_credit_allowed' => (bool) $this->is_credit_allowed,
            'is_advance_allowed' => (bool) $this->is_advance_allowed,
            'is_tax_exempt' => (bool) $this->is_tax_exempt,
            'marketing_consent' => (bool) $this->marketing_consent,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
