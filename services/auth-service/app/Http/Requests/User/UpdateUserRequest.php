<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user    = $this->user();
        $targetId = $this->route('user');

        // User can update themselves, or tenant-admin/super-admin can update anyone
        return $user && (
            (string) $user->id === (string) $targetId ||
            $user->hasAnyRole(['super-admin', 'tenant-admin'])
        );
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => [
                'sometimes', 'string', 'email', 'max:255',
                "unique:users,email,{$userId}",
            ],
            'password' => [
                'sometimes', 'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:30'],
            'org_id'    => ['sometimes', 'nullable', 'string', 'max:36', 'exists:organizations,id'],
            'timezone'  => ['sometimes', 'nullable', 'string', 'timezone'],
            'locale'    => ['sometimes', 'nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata'  => ['sometimes', 'nullable', 'array'],
        ];
    }
}
