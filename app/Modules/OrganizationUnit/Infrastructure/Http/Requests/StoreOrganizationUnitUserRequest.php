<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationUnitUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $organizationUnitId = (int) $this->input('org_unit_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'required|integer|exists:org_units,id',
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('org_unit_users', 'user_id')->where(
                    static fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('org_unit_id', $organizationUnitId)
                ),
            ],
            'role' => 'nullable|string|max:255',
            'is_primary' => 'required|boolean',
        ];
    }
}
