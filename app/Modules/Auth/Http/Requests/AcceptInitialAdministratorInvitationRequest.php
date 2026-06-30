<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Security\PasswordPolicy;
use Modules\Auth\Services\Registration\RegistrationInvitationTokenFormat;

final class AcceptInitialAdministratorInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'size:'.RegistrationInvitationTokenFormat::ENCODED_LENGTH,
                'regex:'.RegistrationInvitationTokenFormat::VALIDATION_PATTERN,
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', PasswordPolicy::rule()],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ];
    }
}
