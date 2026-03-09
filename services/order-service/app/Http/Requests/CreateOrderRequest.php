<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Order Request
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.sku' => ['sometimes', 'string'],
            'items.*.name' => ['sometimes', 'string'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'shipping_address' => ['sometimes', 'array'],
            'shipping_address.street' => ['sometimes', 'string'],
            'shipping_address.city' => ['sometimes', 'string'],
            'shipping_address.country' => ['sometimes', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
