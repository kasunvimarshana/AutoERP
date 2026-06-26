<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;

final class AcceptPlatformOperatorInvitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string,mixed> */
    public function rules(PlatformOperatorCredentialProvisionerInterface $credentials): array
    {
        $requirements = $credentials->passwordRequirements();
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

        return [
            'token' => ['required', 'string', 'size:72'],
            'password' => ['required', 'string', 'max:255', 'confirmed', $password],
        ];
    }
}
