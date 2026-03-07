<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity' => 'sometimes|integer|min:0',
            'reserved_quantity' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'max_quantity' => 'sometimes|nullable|integer|min:0',
        ];
    }
}
