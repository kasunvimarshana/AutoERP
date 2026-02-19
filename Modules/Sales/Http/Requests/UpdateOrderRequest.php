<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Enums\OrderStatus;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'organization_id' => [
                'sometimes',
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'customer_id' => [
                'sometimes',
                'required',
                Rule::exists('customers', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'order_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('orders', 'order_code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($order->id)
                    ->whereNull('deleted_at'),
            ],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'order_date' => ['sometimes', 'required', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:order_date'],
            'subtotal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'terms_conditions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'string', 'exists:order_items,id'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.line_total' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'customer_id' => 'customer',
            'order_code' => 'order code',
            'order_date' => 'order date',
            'delivery_date' => 'delivery date',
            'subtotal' => 'subtotal',
            'tax_amount' => 'tax amount',
            'discount_amount' => 'discount amount',
            'shipping_cost' => 'shipping cost',
            'total_amount' => 'total amount',
            'terms_conditions' => 'terms and conditions',
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'unit price',
            'items.*.tax_rate' => 'tax rate',
            'items.*.discount_rate' => 'discount rate',
            'items.*.line_total' => 'line total',
        ];
    }
}
