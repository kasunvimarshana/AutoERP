<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationUnitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationUnitTypeId = (int) $this->route('organization_unit_type');
        $tenantId = (int) ($this->input('tenant_id') ?? 0);

        return [
            'tenant_id' => 'sometimes|required|integer|exists:tenants,id',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('org_unit_types', 'name')
                    ->ignore($organizationUnitTypeId)
                    ->where(static fn ($query) => $tenantId > 0 ? $query->where('tenant_id', $tenantId) : $query),
            ],
            'level' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|required|boolean',
        ];
    }
}
