<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Security\PasswordPolicy;

final class RegisterRequest extends FormRequest
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
            'provider_key' => ['nullable', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'password' => ['required', 'string', 'max:255', PasswordPolicy::rule()],
            'invitation_token' => ['nullable', 'string', 'size:64'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
