<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Sales Order Line Request
 *
 * Validates data for creating a new sales order line.
 */
class StoreSalesOrderLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Creating an order line requires ability to update the parent sales order
        $salesOrder = \Modules\Sales\Entities\SalesOrder::find($this->input('sales_order_id'));
        if (! $salesOrder) {
            return false;
        }

        return $this->user()->can('update', $salesOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'line_total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
