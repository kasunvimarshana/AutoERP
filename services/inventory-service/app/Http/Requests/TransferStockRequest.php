<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_inventory_id' => ['required', 'uuid'],
            'to_inventory_id'   => ['required', 'uuid', 'different:from_inventory_id'],
            'quantity'          => ['required', 'integer', 'min:1'],
            'notes'             => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_inventory_id.required'  => 'Source inventory ID is required.',
            'to_inventory_id.required'    => 'Destination inventory ID is required.',
            'to_inventory_id.different'   => 'Source and destination inventory must be different.',
            'quantity.required'           => 'Transfer quantity is required.',
            'quantity.min'                => 'Transfer quantity must be at least 1.',
        ];
    }
}
