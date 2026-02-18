<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Sales Order Line Request
 *
 * Validates data for updating an existing sales order line.
 */
class UpdateSalesOrderLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Updating an order line requires ability to update the parent sales order
        $salesOrderLine = $this->route('salesOrderLine') ?? $this->route('sales_order_line');
        if (! $salesOrderLine) {
            return false;
        }

        return $this->user()->can('update', $salesOrderLine->salesOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sales_order_id' => ['sometimes', 'required', 'integer', 'exists:sales_orders,id'],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'line_total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
