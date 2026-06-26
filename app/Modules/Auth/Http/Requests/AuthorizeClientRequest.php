<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AuthorizeClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_key' => ['required', 'string', 'max:120'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
            'scopes' => ['required', 'array', 'min:1', 'max:20'],
            'scopes.*' => ['required', 'string', 'max:120', 'distinct'],
            'code_challenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'code_challenge_method' => ['required', 'in:S256'],
            'state' => ['nullable', 'string', 'max:512'],
            'user_id' => ['prohibited'],
            'session_id' => ['prohibited'],
            'client_secret' => ['prohibited'],
        ];
    }
}
