<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationUnitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('org_unit_types', 'name')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'level' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ];
    }
}
