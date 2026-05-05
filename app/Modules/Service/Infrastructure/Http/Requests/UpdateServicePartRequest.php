<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'integer', 'min:1'],
            'part_source' => ['sometimes', 'string', 'in:inventory,non_inventory,special_order'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quantity' => ['sometimes', 'numeric', 'min:0'],
            'uom_id' => ['sometimes', 'integer', 'min:1'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'is_returned' => ['sometimes', 'boolean'],
            'is_warranty_covered' => ['sometimes', 'boolean'],
            'stock_reference_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'stock_reference_id' => ['sometimes', 'integer', 'min:1'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
