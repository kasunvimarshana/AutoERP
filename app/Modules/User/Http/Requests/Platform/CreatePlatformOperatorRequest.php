<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;

final class CreatePlatformOperatorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(PlatformOperatorCredentialProvisionerInterface $credentials): array
    {
        $password = $this->passwordRule($credentials->passwordRequirements());

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255', 'confirmed', $password],
            'password_confirmation' => ['required', 'string', 'max:255'],
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
