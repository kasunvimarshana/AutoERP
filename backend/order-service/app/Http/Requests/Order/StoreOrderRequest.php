<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.warehouse_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.product_name' => 'sometimes|string|max:255',
            'items.*.product_sku' => 'sometimes|string|max:100',
            'shipping_address' => 'sometimes|array',
            'shipping_address.street' => 'sometimes|string',
            'shipping_address.city' => 'sometimes|string',
            'shipping_address.country' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
