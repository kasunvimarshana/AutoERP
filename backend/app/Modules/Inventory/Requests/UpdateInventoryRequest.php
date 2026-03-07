<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity'           => ['sometimes', 'integer', 'min:0'],
            'reserved_quantity'  => ['sometimes', 'integer', 'min:0'],
            'reorder_point'      => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity'   => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
