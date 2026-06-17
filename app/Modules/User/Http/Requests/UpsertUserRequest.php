<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class UpsertUserRequest extends FormRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->isMethod('post')
            ? $this->canUse(UserPermission::USERS_CREATE)
            : $this->canUse(UserPermission::USERS_UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        $createOnly = $this->isMethod('post') ? ['nullable'] : ['prohibited'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => array_merge($createOnly, ['array']),
            'identity_references' => array_merge($createOnly, ['array']),
            'identity_references.*' => [$this->isMethod('post') ? 'required_with:identity_references' : 'prohibited', 'string', 'max:255'],
            'row_version' => ['sometimes', 'integer', 'min:1'],
            'first_name' => array_merge($required, ['string', 'max:255']),
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email' => array_merge($required, ['email:rfc,dns', 'max:255']),
            'email_verified_at' => $this->isMethod('post') ? ['nullable', 'date'] : ['prohibited'],
            'password' => $this->isMethod('post') ? ['required', 'string', 'min:8', 'max:255'] : ['prohibited'],
            'status' => ['nullable', 'string', 'in:'.implode(',', UserStatus::values())],
            'avatar_path' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'preferences' => ['nullable', 'array'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'min:1'],
            'organization_unit_ids' => ['nullable', 'array'],
            'organization_unit_ids.*' => ['integer', 'min:1'],
            'default_organization_unit_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
