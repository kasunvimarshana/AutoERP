<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IssueTokenRequest extends FormRequest
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
            'provider_id' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'identity_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'integer', 'min:1'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:100'],
            'grant_type' => ['required', 'string', 'max:80'],
            'access_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'refresh_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
