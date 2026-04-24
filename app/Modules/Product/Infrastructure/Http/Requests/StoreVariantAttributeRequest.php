<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $productId = (int) $this->input('product_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'attribute_id' => [
                'required',
                'integer',
                Rule::exists('attributes', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
                Rule::unique('variant_attributes', 'attribute_id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->where('product_id', $productId)
                ),
            ],
            'is_required' => 'nullable|boolean',
            'is_variation_axis' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
