<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'email'       => 'sometimes|required|email|unique:users,email,'.$userId,
            'first_name'  => 'sometimes|required|string|max:255',
            'last_name'   => 'sometimes|required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|array',
            'preferences' => 'nullable|array',
            'active'      => 'boolean',
            'roles'       => 'nullable|array',
            'roles.*'     => 'integer|exists:roles,id',
        ];
    }
}
