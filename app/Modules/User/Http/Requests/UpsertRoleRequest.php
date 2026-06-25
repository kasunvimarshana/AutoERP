<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class UpsertRoleRequest extends TenantScopedRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->isMethod('post')
            ? $this->canUse(UserPermission::ROLES_CREATE)
            : $this->canUse(UserPermission::ROLES_UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'name' => array_merge($required, ['string', 'max:255']),
            'guard_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'min:1'],
            'row_version' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
