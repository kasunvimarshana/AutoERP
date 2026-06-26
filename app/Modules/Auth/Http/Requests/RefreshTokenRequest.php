<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'refresh_token' => ['prohibited'],
            'scopes' => ['prohibited'],
            'access_token_ttl_seconds' => ['prohibited'],
            'refresh_token_ttl_seconds' => ['prohibited'],
            'session_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }
}
