<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

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
        $tenantId = (int) $this->route('tenant');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_settings', 'key')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'value' => 'nullable|array',
            'group' => 'required|string|max:255',
            'is_public' => 'required|boolean',
        ];
    }
}
