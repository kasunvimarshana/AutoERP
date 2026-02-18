<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stock Transaction Request
 *
 * Validates data for recording a stock transaction.
 */
class StockTransactionRequest extends FormRequest
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
            'transaction_type' => 'required|string|in:receipt,issue,adjustment_in,adjustment_out,transfer_in,transfer_out,return,reservation,allocation,release,damaged',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'transaction_date' => 'nullable|date',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|uuid',
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
            'product_id.exists' => 'Selected product does not exist.',
            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'Selected warehouse does not exist.',
            'transaction_type.required' => 'Transaction type is required.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be greater than 0.',
        ];
    }
}
