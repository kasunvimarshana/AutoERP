<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'     => ['required', 'integer', 'exists:products,id'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'integer'],
            'location'       => ['nullable', 'string', 'max:255'],
        ];
    }
}
