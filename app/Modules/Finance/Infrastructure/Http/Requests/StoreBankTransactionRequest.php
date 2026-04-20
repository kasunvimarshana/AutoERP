<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric'],
            'balance' => ['sometimes', 'nullable', 'numeric'],
            'type' => ['required', 'in:debit,credit'],
            'status' => ['sometimes', 'in:imported,categorized,reconciled,excluded'],
        ];
    }
}
