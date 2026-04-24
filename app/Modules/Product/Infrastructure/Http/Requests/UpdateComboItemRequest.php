<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComboItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'combo_product_id' => 'required|integer|exists:products,id',
            'component_product_id' => 'required|integer|exists:products,id',
            'component_variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|numeric|gt:0',
            'uom_id' => 'required|integer|exists:units_of_measure,id',
            'metadata' => 'nullable|array',
        ];
    }
}
