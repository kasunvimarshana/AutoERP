<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'           => ['required', 'integer', 'exists:users,id'],
            'customer_name'         => ['required', 'string', 'max:255'],
            'customer_email'        => ['required', 'email', 'max:255'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.discount'      => ['sometimes', 'numeric', 'min:0'],
            'currency'              => ['sometimes', 'string', 'size:3'],
            'tax'                   => ['sometimes', 'numeric', 'min:0'],
            'discount'              => ['sometimes', 'numeric', 'min:0'],
            'shipping_address'      => ['sometimes', 'nullable', 'array'],
            'billing_address'       => ['sometimes', 'nullable', 'array'],
            'notes'                 => ['sometimes', 'nullable', 'string', 'max:1000'],
            'metadata'              => ['sometimes', 'nullable', 'array'],
        ];
    }
}
