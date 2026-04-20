<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditMemoRequest extends FormRequest
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
            'party_id' => ['required', 'integer'],
            'party_type' => ['required', 'in:customer,supplier'],
            'credit_memo_number' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'issued_date' => ['required', 'date'],
            'status' => ['sometimes', 'in:draft,issued,applied,voided'],
            'applied_to_invoice_id' => ['sometimes', 'nullable', 'integer'],
            'applied_to_invoice_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
