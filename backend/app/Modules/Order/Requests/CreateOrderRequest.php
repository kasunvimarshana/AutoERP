<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['required', 'email', 'max:255'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'tax'                    => ['sometimes', 'numeric', 'min:0'],
            'notes'                  => ['nullable', 'string'],
        ];
    }
}
