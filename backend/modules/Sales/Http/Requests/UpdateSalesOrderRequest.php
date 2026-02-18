<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sales Order Update Request
 * 
 * Validates data for updating existing sales orders
 */
class UpdateSalesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales.orders.update');
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'uuid', 'exists:customers,id'],
            'order_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'delivery_date' => ['nullable', 'date', 'after:order_date'],
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
            'shipping_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'billing_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:draft,confirmed,processing,shipped,delivered,cancelled'],
            
            // Line items
            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.product_id' => ['required_with:line_items', 'exists:products,id'],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required_with:line_items', 'numeric', 'min:0'],
            'line_items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid order status. Must be one of: draft, confirmed, processing, shipped, delivered, cancelled',
            'line_items.min' => 'Sales order must have at least one product',
        ];
    }
}
