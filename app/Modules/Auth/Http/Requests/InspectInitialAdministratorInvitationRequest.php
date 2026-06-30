<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Services\Registration\RegistrationInvitationTokenFormat;

final class InspectInitialAdministratorInvitationRequest extends FormRequest
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
        ];
    }
}
