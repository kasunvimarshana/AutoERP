<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAdvancePaymentRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'party_type' => array_merge($required, ['string', 'max:255']),
            'party_id' => array_merge($required, ['integer', 'min:1']),
            'reference' => ['nullable', 'string', 'max:255'],
            'advance_number' => array_merge($required, ['string', 'max:255']),
            'amount' => array_merge($required, ['numeric']),
            'remaining_amount' => array_merge($required, ['numeric']),
            'advance_date' => array_merge($required, ['date']),
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'payment_id' => ['nullable', 'integer', 'min:1', 'exists:payments,id'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1']
        ];
    }
}