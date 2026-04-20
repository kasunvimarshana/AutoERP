<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'integer'],
            'bank_account_id' => ['sometimes', 'integer'],
            'type' => ['sometimes', 'in:debit,credit'],
            'status' => ['sometimes', 'in:imported,categorized,reconciled,excluded'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string'],
        ];
    }
}
