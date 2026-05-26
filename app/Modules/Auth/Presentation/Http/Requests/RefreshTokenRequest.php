<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
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
            'refresh_token' => ['required', 'string', 'min:10'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:100'],
            'access_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'refresh_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
