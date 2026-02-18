<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_number' => ['nullable', 'string', 'max:50', 'unique:payments,payment_number'],
            'customer_id' => ['required', 'integer'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,check,bank_transfer,credit_card,debit_card,online,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tags' => ['nullable', 'array'],
        ];
    }
}
