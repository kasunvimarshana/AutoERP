<?php

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
