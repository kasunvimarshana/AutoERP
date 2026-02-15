<?php

declare(strict_types=1);

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class StoreBOMRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add policy check: $this->user()->can('create', BillOfMaterial::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'bom_number' => ['nullable', 'string', 'max:50', 'unique:bill_of_materials,bom_number'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:draft,active,inactive,archived'],
            'quantity' => ['nullable', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'production_time_minutes' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            
            // BOM Items
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'items.*.scrap_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.sequence' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Selected product does not exist',
            'name.required' => 'BOM name is required',
            'valid_until.after_or_equal' => 'Valid until date must be after or equal to valid from date',
            'items.*.product_id.required' => 'Component product is required',
            'items.*.product_id.exists' => 'Selected component product does not exist',
            'items.*.quantity.required' => 'Component quantity is required',
        ];
    }
}
