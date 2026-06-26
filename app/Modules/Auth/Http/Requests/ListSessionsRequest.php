<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListSessionsRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
