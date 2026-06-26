<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RevokeSessionRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
            'user_id' => ['prohibited'],
            'session_id' => ['prohibited'],
        ];
    }
}
