<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateOrderRequest — validates POST /api/orders
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items'                        => ['required', 'array', 'min:1'],
            'items.*.product_id'           => ['required', 'string', 'uuid'],
            'items.*.product_name'         => ['sometimes', 'string', 'max:255'],
            'items.*.product_code'         => ['sometimes', 'string', 'max:100'],
            'items.*.product_sku'          => ['sometimes', 'string', 'max:100'],
            'items.*.quantity'             => ['required', 'integer', 'min:1'],
            'items.*.unit_price'           => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount'      => ['sometimes', 'numeric', 'min:0'],
            'items.*.currency'             => ['sometimes', 'string', 'size:3'],
            'currency'                     => ['sometimes', 'string', 'size:3'],
            'notes'                        => ['sometimes', 'nullable', 'string', 'max:2000'],
            'metadata'                     => ['sometimes', 'array'],
            'shipping_address'             => ['sometimes', 'array'],
            'shipping_address.line1'       => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.city'        => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.country'     => ['required_with:shipping_address', 'string', 'size:2'],
            'shipping_address.postal_code' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
