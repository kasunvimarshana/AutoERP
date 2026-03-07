<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $userId],
            'password' => $this->isMethod('post')
                ? ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()]
                : ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'phone'  => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'role'   => ['nullable', 'string', 'exists:roles,name'],
        ];
    }
}
