<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Sales Order Request
 *
 * Validates data for updating an existing sales order.
 */
class UpdateSalesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $salesOrder = $this->route('salesOrder') ?? $this->route('sales_order');

        return $this->user()->can('update', $salesOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orderId = $this->route('sales_order') ?? $this->route('id');

        return [
            'order_number' => ['nullable', 'string', 'max:50', "unique:sales_orders,order_number,{$orderId}"],
            'customer_id' => ['sometimes', 'required', 'integer', 'exists:customers,id'],
            'order_date' => ['sometimes', 'required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['nullable', 'in:draft,confirmed,processing,shipped,delivered,cancelled'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid,refunded'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
        ];
    }
}
