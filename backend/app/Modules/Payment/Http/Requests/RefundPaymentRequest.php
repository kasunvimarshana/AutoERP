<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_number' => ['required', 'string', 'max:100', 'unique:payment_refunds,refund_number'],
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string'],
            'processed_by' => ['nullable', 'string', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_number.required' => 'Refund number is required',
            'refund_number.unique' => 'This refund number is already taken',
            'refund_date.required' => 'Refund date is required',
            'refund_date.date' => 'Refund date must be a valid date',
            'amount.required' => 'Refund amount is required',
            'amount.numeric' => 'Refund amount must be a number',
            'amount.min' => 'Refund amount must be greater than 0',
            'reason.required' => 'Refund reason is required',
            'processed_by.exists' => 'The selected user does not exist',
        ];
    }
}
