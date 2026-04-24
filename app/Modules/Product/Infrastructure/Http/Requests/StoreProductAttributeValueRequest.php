<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');
        $attributeId = $this->input('attribute_id');

        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'attribute_id' => 'required|integer|exists:attributes,id',
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'value')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('attribute_id', $attributeId)
                ),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
