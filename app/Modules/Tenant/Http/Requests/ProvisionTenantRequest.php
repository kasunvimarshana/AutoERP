<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\User\Contracts\TenantUserCredentialProvisionerInterface;

final class ProvisionTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(TenantUserCredentialProvisionerInterface $credentials): array
    {
        $password = $this->passwordRule($credentials->passwordRequirements());

        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'initial_admin_first_name' => ['required', 'string', 'max:100'],
            'initial_admin_last_name' => ['nullable', 'string', 'max:100'],
            'initial_admin_email' => ['required', 'email:rfc', 'max:255'],
            'initial_admin_password' => ['required', 'string', 'max:255', 'confirmed', $password],
            'initial_admin_password_confirmation' => ['required', 'string', 'max:255'],
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
