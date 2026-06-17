<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class UpsertUserRoleRequest extends FormRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->canUse(UserPermission::USERS_ASSIGN_ROLES);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'user_id' => array_merge($required, ['integer', 'min:1', 'exists:users,id']),
            'role_id' => array_merge($required, ['integer', 'min:1', 'exists:roles,id']),
        ];
    }
}
