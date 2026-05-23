<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'type_id' => ['nullable', 'integer', Rule::exists('organization_unit_types', 'id')->where('tenant_id', $tenantId)],
            'parent_id' => ['nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', Rule::unique('organization_units', 'name')->where('tenant_id', $tenantId)->ignore($this->route('unit'))],
            'code' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            '_lft' => ['nullable', 'integer'],
            '_rgt' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
