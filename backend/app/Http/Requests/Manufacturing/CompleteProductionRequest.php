<?php

declare(strict_types=1);

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProductionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add policy check: $this->user()->can('complete', $workOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'produced_quantity' => ['nullable', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'scrap_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'produced_quantity.min' => 'Produced quantity must be greater than zero',
        ];
    }
}
