<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Request
 *
 * Validates user login input.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'tenant_id' => ['sometimes', 'integer'],
            'device_name' => ['sometimes', 'string', 'max:255'],
            'revoke_previous' => ['sometimes', 'boolean'],
        ];
    }
}
