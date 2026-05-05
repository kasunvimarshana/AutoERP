<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationUnitId = $this->route('organization_unit');

        return [
            'type_id' => 'sometimes|nullable|integer|exists:org_unit_types,id',
            'parent_id' => 'sometimes|nullable|integer|exists:org_units,id',
            'manager_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:100|unique:org_units,code,'.$organizationUnitId,
            'metadata' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|boolean',
            'description' => 'sometimes|nullable|string',
            'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'row_version' => 'sometimes|integer|min:1',
        ];
    }
}
