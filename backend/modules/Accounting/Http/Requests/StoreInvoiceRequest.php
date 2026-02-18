<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Invoice Creation Request
 * 
 * Validates data for generating new invoices
 */
class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()->can('accounting.invoices.create');
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms_and_conditions' => ['nullable', 'string', 'max:5000'],
            
            // Line items
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.account_code' => ['nullable', 'string', 'max:50'],
            
            // Totals and adjustments
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'adjustment_amount' => ['nullable', 'numeric'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            
            // Additional fields
            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required for invoice',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'line_items.required' => 'Invoice must have at least one line item',
            'line_items.min' => 'Invoice requires at least one item',
            'total_amount.min' => 'Invoice total must be greater than or equal to zero',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set default currency if not provided
        if (!$this->has('currency_code')) {
            $this->merge([
                'currency_code' => config('app.currency', 'USD'),
            ]);
        }
        
        // Set default exchange rate if not provided
        if (!$this->has('exchange_rate')) {
            $this->merge([
                'exchange_rate' => 1.0,
            ]);
        }
    }
}
