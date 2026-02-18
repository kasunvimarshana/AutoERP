<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'batch_number' => ['nullable', 'string', 'max:255', 'unique:batches,batch_number'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'uuid'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:manufacture_date'],
            'received_quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'custom_attributes' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'variant_id' => 'product variant',
            'batch_number' => 'batch number',
            'lot_number' => 'lot number',
            'supplier_id' => 'supplier',
            'manufacture_date' => 'manufacture date',
            'expiry_date' => 'expiry date',
            'received_quantity' => 'received quantity',
            'unit_cost' => 'unit cost',
        ];
    }
}
