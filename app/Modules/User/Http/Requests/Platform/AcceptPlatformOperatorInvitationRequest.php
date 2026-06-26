<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Security\PasswordPolicy;

final class AcceptPlatformOperatorInvitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:72'],
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ];
    }
}
