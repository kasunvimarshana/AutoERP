<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'reorder_level'      => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity'   => ['sometimes', 'integer', 'min:1'],
            'unit_cost'          => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'status'             => ['sometimes', 'string', 'in:active,inactive'],
            'last_counted_at'    => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'reorder_level.integer'   => 'Reorder level must be a whole number.',
            'reorder_level.min'       => 'Reorder level must be zero or greater.',
            'reorder_quantity.integer' => 'Reorder quantity must be a whole number.',
            'reorder_quantity.min'    => 'Reorder quantity must be at least 1.',
            'unit_cost.numeric'       => 'Unit cost must be a valid number.',
            'unit_cost.min'           => 'Unit cost must be zero or greater.',
            'status.in'               => 'Status must be either active or inactive.',
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
