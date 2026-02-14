<?php

namespace App\Modules\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['nullable', 'string', 'exists:product_variants,id'],
            'description' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'line_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.exists' => 'The selected product variant does not exist',
            'description.required' => 'Description is required',
            'quantity.required' => 'Quantity is required',
            'quantity.numeric' => 'Quantity must be a number',
            'quantity.min' => 'Quantity must be greater than zero',
            'unit_price.required' => 'Unit price is required',
            'unit_price.numeric' => 'Unit price must be a number',
            'unit_price.min' => 'Unit price cannot be negative',
            'tax_rate.numeric' => 'Tax rate must be a number',
            'tax_rate.min' => 'Tax rate cannot be negative',
            'tax_rate.max' => 'Tax rate cannot exceed 100%',
            'discount_amount.numeric' => 'Discount amount must be a number',
            'discount_amount.min' => 'Discount amount cannot be negative',
            'line_total.numeric' => 'Line total must be a number',
            'line_total.min' => 'Line total cannot be negative',
        ];
    }
}
