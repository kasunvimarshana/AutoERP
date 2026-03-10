<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * RegisterRequest — validates POST /api/auth/register
 */
class RegisterRequest extends FormRequest
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
            'name'      => ['required', 'string', 'min:2', 'max:255'],
            'email'     => ['required', 'email:rfc,dns', 'max:255'],
            'password'  => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'tenant_id' => ['required', 'string', 'uuid'],
            'role'      => ['sometimes', 'nullable', 'string', 'max:64'],
            'metadata'  => ['sometimes', 'array'],
        ];
    }
}
