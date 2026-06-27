<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertPostingProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $updating = is_numeric($this->route('profile'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => [$updating ? 'required' : 'nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.line_key' => ['required', 'string', 'max:100', 'distinct'],
            'rules.*.account_role_id' => ['required', 'integer', 'min:1', $this->tenantExists('finance_account_roles', 'id')],
            'rules.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
