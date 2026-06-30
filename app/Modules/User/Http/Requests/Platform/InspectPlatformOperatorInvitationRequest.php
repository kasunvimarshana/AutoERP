<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;

final class InspectPlatformOperatorInvitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'size:'.PlatformOperatorInvitationTokenCodec::ENCODED_LENGTH,
                'regex:'.PlatformOperatorInvitationTokenCodec::VALIDATION_PATTERN,
            ],
        ];
    }
}
