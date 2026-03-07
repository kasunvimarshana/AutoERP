<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'quantity'           => ['required', 'integer', 'min:0'],
            'reserved_quantity'  => ['sometimes', 'integer', 'min:0'],
            'reorder_point'      => ['required', 'integer', 'min:0'],
            'reorder_quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
