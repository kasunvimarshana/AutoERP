<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlatformLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:320'],
            'password' => ['required', 'string', 'max:1024'],
            'totp_code' => ['nullable', 'string', 'regex:/^\d{6}$/', 'prohibits:backup_code'],
            'backup_code' => ['nullable', 'string', 'max:32', 'prohibits:totp_code'],
            'device_name' => ['nullable', 'string', 'max:160'],
        ];
    }
}
