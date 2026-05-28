<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SupplierFinanceDefaultsRequest extends FormRequest
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
            'default_payable_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'default_expense_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
