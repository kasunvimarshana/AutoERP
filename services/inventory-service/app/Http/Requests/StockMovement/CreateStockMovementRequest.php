<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid', 'exists:products,id'],
            'warehouse_id'     => ['required', 'uuid', 'exists:warehouses,id'],
            'quantity'         => ['required', 'numeric', 'not_in:0'],
            'type'             => [
                'required',
                Rule::in(array_values(config('inventory.movement_types', [
                    'receipt', 'issue', 'transfer_in', 'transfer_out',
                    'adjustment', 'reservation', 'release', 'commit',
                ]))),
            ],
            'reference_id'     => ['nullable', 'string', 'max:255'],
            'reference_type'   => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'metadata'         => ['nullable', 'array'],
            'performed_at'     => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.not_in' => 'Quantity cannot be zero.',
            'type.in'         => 'Movement type must be one of: receipt, issue, transfer_in, transfer_out, adjustment, reservation, release, commit.',
        ];
    }
}
