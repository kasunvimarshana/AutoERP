<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',  // Would reference internal users DB or auth token
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
