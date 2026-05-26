<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAdvancePaymentRequest extends FormRequest
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
            'created_by' => $this->input('created_by', $this->attributes->get('current_user_id')),
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
            'advance_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'advance_date' => ['required', 'date'],
            'payment_id' => ['nullable', 'integer', 'min:1', 'exists:payments,id'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
