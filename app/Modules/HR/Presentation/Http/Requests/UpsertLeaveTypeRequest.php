<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertLeaveTypeRequest extends FormRequest
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
            'name' => array_merge($required, ['string', 'max:255']),
            'code' => array_merge($required, ['string', 'max:255']),
            'description' => ['nullable', 'string'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'max_days_per_year' => ['nullable', 'numeric'],
            'carry_forward_max' => ['nullable', 'numeric'],
            'allow_negative_balance' => ['nullable', 'boolean'],
            'applicable_gender' => ['nullable', 'string', 'max:255'],
            'min_service_days' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
