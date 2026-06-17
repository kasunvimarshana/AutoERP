<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class AssignUserToOrganizationUnitRequest extends FormRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->canUse(UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
