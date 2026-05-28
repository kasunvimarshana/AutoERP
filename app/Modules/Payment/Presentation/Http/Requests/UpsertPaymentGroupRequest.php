<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPaymentGroupRequest extends FormRequest
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
            'transaction_number' => ['nullable', 'string', 'max:255'],
            'group_type' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'total_amount' => ['nullable', 'numeric', 'gte:0'],
            'status' => ['nullable', 'in:draft,posted,reconciled,voided'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
