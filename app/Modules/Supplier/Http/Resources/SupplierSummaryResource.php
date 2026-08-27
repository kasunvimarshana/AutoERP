<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierSummaryResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'supplier_number' => $this->supplier_number,
            'code' => $this->code,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'supplier_type' => $this->enumValue($this->supplier_type),
            'status' => $this->enumValue($this->status),
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'default_currency' => $this->relationLoaded('defaultCurrency')
                ? $this->namedResource($this->defaultCurrency, true)
                : null,
            'categories' => $this->whenLoaded('categories', fn () => SupplierCategoryResource::collection($this->categories)->resolve($request)),
            'is_credit_allowed' => (bool) $this->is_credit_allowed,
            'is_advance_allowed' => (bool) $this->is_advance_allowed,
            'total_due' => $this->when(
                $this->resource->getAttribute('total_due') !== null,
                fn (): array => (array) $this->resource->getAttribute('total_due'),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
