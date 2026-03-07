<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RBAC middleware
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer', 'min:1'],
            'warehouse_id'     => ['required', 'integer', 'min:1'],
            'sku'              => ['required', 'string', 'max:100'],
            'quantity'         => ['sometimes', 'integer', 'min:0'],
            'reorder_point'    => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'unit_cost'        => ['nullable', 'numeric', 'min:0'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
