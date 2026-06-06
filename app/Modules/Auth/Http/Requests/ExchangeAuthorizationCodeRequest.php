<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExchangeAuthorizationCodeRequest extends FormRequest
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
            'authorization_code' => ['required', 'string', 'min:10'],
            'client_key' => ['required', 'string', 'max:120'],
            'client_secret' => ['nullable', 'string', 'min:1'],
            'redirect_uri' => ['nullable', 'url', 'max:2048'],
            'code_verifier' => ['nullable', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:100'],
            'access_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'refresh_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
