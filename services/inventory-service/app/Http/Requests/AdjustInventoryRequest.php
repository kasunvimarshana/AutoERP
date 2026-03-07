<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\InventoryTransaction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'           => ['required', 'string', Rule::in(InventoryTransaction::VALID_TYPES)],
            'quantity'       => ['required', 'integer', 'not_in:0'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'string', 'max:100'],
            'performed_by'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $validTypes = implode(', ', InventoryTransaction::VALID_TYPES);

        return [
            'type.required' => 'Transaction type is required.',
            'type.in'       => "Transaction type must be one of: {$validTypes}.",
            'quantity.required' => 'Quantity is required.',
            'quantity.integer'  => 'Quantity must be a whole number.',
            'quantity.not_in'   => 'Quantity must not be zero.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
