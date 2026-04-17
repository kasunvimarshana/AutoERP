<?php

declare(strict_types=1);

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
            'tenant_id' => 'required|integer|exists:tenants,id',
            'type_id' => 'nullable|integer|exists:org_unit_types,id',
            'parent_id' => 'nullable|integer|exists:org_units,id',
            'manager_user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:org_units,code',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
