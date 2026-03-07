<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id'     => ['sometimes', 'integer', 'min:1'],
            'sku'              => ['sometimes', 'string', 'max:100'],
            'reorder_point'    => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'unit_cost'        => ['nullable', 'numeric', 'min:0'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
