<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sales Order Creation Request
 * 
 * Validates data for creating new sales orders with line items
 */
class StoreSalesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales.orders.create');
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'delivery_date' => ['nullable', 'date', 'after:order_date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
            'shipping_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'billing_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            
            // Line items
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.product_id' => ['required', 'exists:products,id'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.notes' => ['nullable', 'string', 'max:500'],
            
            // Pricing and discounts
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_inclusive' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer selection is required for sales order',
            'customer_id.exists' => 'Selected customer does not exist',
            'delivery_date.after' => 'Delivery date must be after order date',
            'line_items.required' => 'At least one line item is required',
            'line_items.min' => 'Sales order must have at least one product',
            'line_items.*.product_id.required' => 'Product is required for each line item',
            'line_items.*.quantity.min' => 'Quantity must be greater than zero',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'order_date' => 'order date',
            'delivery_date' => 'delivery date',
            'warehouse_id' => 'warehouse',
            'line_items.*.product_id' => 'product',
            'line_items.*.quantity' => 'quantity',
            'line_items.*.unit_price' => 'unit price',
        ];
    }
}
