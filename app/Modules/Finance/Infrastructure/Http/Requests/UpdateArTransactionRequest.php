<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'customer_id' => ['required', 'integer'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'transaction_type' => ['required', 'in:invoice,payment,credit_memo,adjustment'],
            'amount' => ['required', 'numeric'],
            'balance_after' => ['required', 'numeric'],
            'transaction_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'is_reconciled' => ['sometimes', 'boolean'],
        ];
    }
}
