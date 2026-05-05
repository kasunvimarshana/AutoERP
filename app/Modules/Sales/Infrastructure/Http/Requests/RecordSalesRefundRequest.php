<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordSalesRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_number' => ['required', 'string', 'max:100'],
            'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'account_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0.000001'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'refund_date' => ['required', 'date'],
            'exchange_rate' => ['sometimes', 'numeric', 'min:0.000001'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
