<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'type' => ['sometimes', 'string', 'in:sale,purchase,return'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'shipping_address' => ['sometimes', 'array'],
            'billing_address' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
            'lines' => ['sometimes', 'array'],
            'lines.*.product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'lines.*.product_name' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
