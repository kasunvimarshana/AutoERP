<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'group_id' => ['required', 'integer', Rule::exists('tenant_setting_groups', 'id')->where('tenant_id', $tenantId)],
            'key' => ['required', 'string', 'max:255', Rule::unique('tenant_settings', 'key')->where('tenant_id', $tenantId)],
            'value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
