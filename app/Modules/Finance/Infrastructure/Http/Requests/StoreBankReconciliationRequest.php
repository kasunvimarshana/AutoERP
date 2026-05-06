<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $base = [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
            'status' => ['sometimes', 'in:draft,completed'],
        ];
        if ('List' === 'Store') {
            return [
                'tenant_id' => ['sometimes', 'integer'],
                'bank_account_id' => ['sometimes', 'integer'],
                'status' => ['sometimes', 'in:draft,completed'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'sort' => ['sometimes', 'string'],
            ];
        }

        return $base;
    }
}
