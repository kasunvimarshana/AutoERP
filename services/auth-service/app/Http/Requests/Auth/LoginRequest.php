<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginRequest — validates POST /api/auth/login
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email'     => ['required', 'email:rfc,dns'],
            'password'  => ['required', 'string', 'min:8'],
            'tenant_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'     => 'An email address is required.',
            'email.email'        => 'Please provide a valid email address.',
            'password.required'  => 'A password is required.',
            'password.min'       => 'Password must be at least 8 characters.',
            'tenant_id.required' => 'A tenant ID is required.',
            'tenant_id.uuid'     => 'Tenant ID must be a valid UUID.',
        ];
    }
}
