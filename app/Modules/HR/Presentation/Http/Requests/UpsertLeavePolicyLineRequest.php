<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertLeavePolicyLineRequest extends FormRequest
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
            'leave_policy_id' => array_merge($required, ['integer', 'min:1', 'exists:leave_policies,id']),
            'leave_type_id' => array_merge($required, ['integer', 'min:1', 'exists:leave_types,id']),
            'annual_allocation' => array_merge($required, ['numeric']),
            'accrual_type' => ['nullable', 'string', 'max:255'],
            'accrual_amount' => ['nullable', 'numeric'],
            'carry_forward_max' => ['nullable', 'numeric'],
        ];
    }
}
