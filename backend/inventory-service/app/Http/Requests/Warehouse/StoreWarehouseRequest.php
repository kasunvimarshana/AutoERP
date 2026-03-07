<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string|max:100',
            'country' => 'sometimes|nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
