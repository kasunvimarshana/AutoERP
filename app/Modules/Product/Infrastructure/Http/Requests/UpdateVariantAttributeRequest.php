<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'required|integer|exists:products,id',
            'attribute_id' => 'required|integer|exists:attributes,id',
            'is_required' => 'nullable|boolean',
            'is_variation_axis' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
