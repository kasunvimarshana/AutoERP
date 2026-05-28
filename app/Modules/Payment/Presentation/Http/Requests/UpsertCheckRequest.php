<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCheckRequest extends FormRequest
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
            'check_number' => array_merge($required, ['string', 'max:255']),
            'type' => array_merge($required, ['in:inbound,outbound']),
            'party_type' => ['nullable', 'string', 'max:255'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'bank_account_id' => array_merge($required, ['integer', 'min:1', 'exists:bank_accounts,id']),
            'payment_id' => ['nullable', 'integer', 'min:1', 'exists:payments,id'],
            'check_date' => array_merge($required, ['date']),
            'due_date' => ['nullable', 'date'],
            'amount' => array_merge($required, ['numeric', 'gt:0']),
            'status' => ['nullable', 'in:pending,received,issued,deposited,cleared,bounced,cancelled,voided'],
            'clearance_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'cleared_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
        ];
    }
}
