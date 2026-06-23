<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlatformRefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:10'],
            'access_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'refresh_token_ttl_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
