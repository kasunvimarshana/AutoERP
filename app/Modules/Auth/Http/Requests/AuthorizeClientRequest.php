<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class AuthorizeClientRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'client_key' => ['required', 'string', 'max:120'],
            'client_secret' => ['nullable', 'string', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'identity_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'integer', 'min:1'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:100'],
            'redirect_uri' => ['nullable', 'url', 'max:2048'],
            'code_challenge' => ['nullable', 'string', 'max:255'],
            'code_challenge_method' => ['nullable', 'string', 'max:20'],
            'ttl_seconds' => ['nullable', 'integer', 'min:30'],
        ];
    }
}
