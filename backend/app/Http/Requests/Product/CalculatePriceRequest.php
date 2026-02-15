<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Uses ProductPolicy to verify user can view products (required to calculate prices).
     */
    public function authorize(): bool
    {
        // User must be able to view products to calculate their prices
        return $this->user()->can('viewAny', \App\Modules\Product\Models\Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'context' => ['nullable', 'array'],
            'context.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'context.price_list_id' => ['nullable', 'integer', 'exists:price_lists,id'],
            'context.discount_type' => ['nullable', 'in:none,flat,percentage'],
            'context.discount_value' => ['nullable', 'numeric', 'min:0'],
            'context.tax_included' => ['nullable', 'boolean'],
            'context.apply_promotions' => ['nullable', 'boolean'],
            'context.coupon_code' => ['nullable', 'string', 'max:100'],
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
            'quantity.required' => 'Quantity is required',
            'quantity.min' => 'Quantity must be greater than 0',
            'quantity.numeric' => 'Quantity must be a valid number',
            'context.customer_id.exists' => 'Selected customer does not exist',
            'context.price_list_id.exists' => 'Selected price list does not exist',
            'context.discount_type.in' => 'Invalid discount type',
            'context.discount_value.min' => 'Discount value cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('context') && !is_array($this->input('context'))) {
            $this->merge(['context' => []]);
        }

        // Set default context values
        if ($this->has('context')) {
            $context = $this->input('context', []);
            
            $context['apply_promotions'] = $context['apply_promotions'] ?? true;
            $context['tax_included'] = $context['tax_included'] ?? false;
            
            $this->merge(['context' => $context]);
        }
    }
}
