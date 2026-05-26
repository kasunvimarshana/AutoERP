<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->input('tenant_id', $this->attributes->get('current_tenant_id')),
            'organization_unit_id' => $this->input(
                'organization_unit_id',
                $this->attributes->get('current_organization_unit_id'),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'party_id' => ['required', 'integer', 'min:1', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_number' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['required', 'integer', 'min:1', 'exists:payment_methods,id'],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.document_type' => ['required', 'string', 'max:255'],
            'allocations.*.document_id' => ['required', 'integer', 'min:1'],
            'allocations.*.reference' => ['nullable', 'string', 'max:255'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
