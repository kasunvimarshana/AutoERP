<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationUnitSettingGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');
        $unitId = $this->route('unit');

        return [
            'key' => ['required', 'string', 'max:255', Rule::unique('organization_unit_setting_groups', 'key')->where('tenant_id', $tenantId)->where('organization_unit_id', $unitId)->ignore($this->route('setting_group'))],
            'value' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', Rule::exists('organization_unit_setting_groups', 'id')->where('tenant_id', $tenantId)->where('organization_unit_id', $unitId)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
