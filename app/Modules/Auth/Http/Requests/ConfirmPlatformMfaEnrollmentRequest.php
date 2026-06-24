<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmPlatformMfaEnrollmentRequest extends FormRequest
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
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }
}
