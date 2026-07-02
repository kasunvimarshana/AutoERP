<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Illuminate\Validation\Rules\Password;
use Modules\User\Constants\UserPermission;
use Modules\User\Contracts\TenantUserCredentialProvisionerInterface;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class CreateUserRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_CREATE; }

    public function rules(TenantUserCredentialProvisionerInterface $credentials): array
    {
        $password = $this->passwordRule($credentials->passwordRequirements());

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'min:1', 'distinct'],
            'organization_unit_ids' => ['required', 'array', 'min:1'],
            'organization_unit_ids.*' => ['integer', 'min:1', 'distinct'],
            'default_organization_unit_id' => ['required', 'integer', 'min:1'],
            'password' => ['required', 'string', 'max:255', 'confirmed', $password],
            'password_confirmation' => ['required', 'string', 'max:255'],
            'status' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }

    /** @param array{minimum_length:int,mixed_case:bool,numbers:bool,symbols:bool} $requirements */
    private function passwordRule(array $requirements): Password
    {
        $password = Password::min($requirements['minimum_length']);
        if ($requirements['mixed_case']) {
            $password->mixedCase();
        }
        if ($requirements['numbers']) {
            $password->numbers();
        }
        if ($requirements['symbols']) {
            $password->symbols();
        }

        return $password;
    }
}
