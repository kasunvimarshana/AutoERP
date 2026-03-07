<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:200'],
            'code'      => ['sometimes', 'string', 'max:50', 'alpha_dash'],
            'address'   => ['nullable', 'string', 'max:500'],
            'city'      => ['nullable', 'string', 'max:100'],
            'country'   => ['nullable', 'string', 'size:2'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
