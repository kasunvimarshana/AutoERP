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
            'quantity' => 'sometimes|integer|min:0',
            'reserved_quantity' => 'sometimes|integer|min:0',
            'minimum_quantity' => 'sometimes|integer|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:available,low_stock,out_of_stock,reserved',
            'metadata' => 'nullable|array',
        ];
    }
}
