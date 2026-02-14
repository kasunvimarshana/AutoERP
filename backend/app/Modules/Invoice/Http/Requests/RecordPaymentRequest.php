<?php

namespace App\Modules\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a number',
            'amount.min' => 'Payment amount must be greater than zero',
        ];
    }
}
