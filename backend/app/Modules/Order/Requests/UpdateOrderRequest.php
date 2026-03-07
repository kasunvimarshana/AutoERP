<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],
            'status'         => ['sometimes', 'in:pending,confirmed,processing,shipped,delivered,cancelled'],
            'notes'          => ['sometimes', 'nullable', 'string'],
        ];
    }
}
