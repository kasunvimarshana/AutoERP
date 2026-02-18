<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stock Adjustment Request
 *
 * Validates data for stock adjustments.
 */
class StockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'variant_id' => 'nullable|uuid|exists:product_variants,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'quantity' => 'required|numeric', // Can be positive (increase) or negative (decrease)
            'reason' => 'required|string|in:physical_count,damaged,expired,theft,correction,other',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'warehouse_id.required' => 'Warehouse is required.',
            'quantity.required' => 'Quantity is required.',
            'reason.required' => 'Adjustment reason is required.',
            'reason.in' => 'Invalid adjustment reason.',
        ];
    }
}
