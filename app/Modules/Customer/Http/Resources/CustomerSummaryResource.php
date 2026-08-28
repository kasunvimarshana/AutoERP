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
        $creditProfile = $this->relationLoaded('creditProfile') ? $this->creditProfile : null;

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
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
            'credit_allowed' => (bool) ($creditProfile?->credit_allowed ?? false),
            'advance_allowed' => (bool) ($creditProfile?->advance_allowed ?? false),
            'current_vehicles' => $this->when(
                $this->resource->getAttribute('current_vehicles') !== null,
                fn (): array => (array) $this->resource->getAttribute('current_vehicles'),
            ),
            'is_tax_exempt' => (bool) $this->is_tax_exempt,
            'marketing_consent' => (bool) $this->marketing_consent,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
