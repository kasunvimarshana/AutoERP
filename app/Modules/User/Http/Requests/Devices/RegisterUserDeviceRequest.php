<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Devices;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserDevicePlatform;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class RegisterUserDeviceRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array
    {
        return [
            'device_token' => ['required', 'string', 'min:20', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:160'],
            'platform' => ['required', Rule::in(UserDevicePlatform::values())],
            'last_seen_at' => ['prohibited'],
            'revoked_at' => ['prohibited'],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }
}
