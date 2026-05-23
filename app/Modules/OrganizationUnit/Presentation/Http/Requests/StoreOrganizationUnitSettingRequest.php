<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationUnitSettingRequest extends FormRequest
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
            'group_id' => ['required', 'integer', Rule::exists('organization_unit_setting_groups', 'id')->where('tenant_id', $tenantId)->where('organization_unit_id', $unitId)],
            'key' => ['required', 'string', 'max:255', Rule::unique('organization_unit_settings', 'key')->where('tenant_id', $tenantId)->where('organization_unit_id', $unitId)->where('group_id', $this->input('group_id'))],
            'value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
