<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReplaceInitialAdministratorInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_invitation_version' => ['required', 'integer', 'min:1'],
            'expected_onboarding_version' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
