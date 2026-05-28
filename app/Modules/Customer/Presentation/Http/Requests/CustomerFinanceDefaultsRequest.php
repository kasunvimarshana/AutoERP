<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CustomerFinanceDefaultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'default_payment_term_id' => ['nullable', 'integer', 'min:1', 'exists:payment_terms,id'],
            'default_receivable_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'default_income_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'requested_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
