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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'authorization_code' => ['required', 'string', 'max:512'],
            'client_key' => ['required', 'string', 'max:120'],
            'client_secret' => ['nullable', 'string', 'max:1024'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
            'code_verifier' => ['required', 'string', 'regex:/^[A-Za-z0-9._~-]{43,128}$/'],
            'scopes' => ['prohibited'],
            'user_id' => ['prohibited'],
            'session_id' => ['prohibited'],
        ];
    }
}
