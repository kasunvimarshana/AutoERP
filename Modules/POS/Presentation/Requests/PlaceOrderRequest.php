<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id'             => ['required', 'uuid'],
            'customer_id'            => ['nullable', 'uuid'],
            'payment_method'         => ['sometimes', 'string', 'in:cash,card,digital_wallet,credit'],
            'cash_tendered'          => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'currency'               => ['sometimes', 'string', 'size:3'],
            'lines'                  => ['required', 'array', 'min:1'],
            'lines.*.product_id'     => ['nullable', 'uuid'],
            'lines.*.product_name'   => ['required', 'string', 'max:255'],
            'lines.*.quantity'       => ['required', 'numeric', 'min:0.00000001'],
            'lines.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'lines.*.discount'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
