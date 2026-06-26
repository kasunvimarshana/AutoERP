<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Devices;

use Modules\User\Http\Requests\AuthorizedUserRequest;

final class ListUserDevicesRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array
    {
        return [
            'include_revoked' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
