<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'uuid'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'total_amount'            => ['required', 'numeric', 'min:0'],
            'currency'                => ['sometimes', 'string', 'size:3'],
            'payment_method'          => ['sometimes', 'string', 'in:credit_card,debit_card,paypal,bank_transfer'],
            'user_email'              => ['sometimes', 'email'],
            'notes'                   => ['nullable', 'string', 'max:500'],
        ];
    }
}
