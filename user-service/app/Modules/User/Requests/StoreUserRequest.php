<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keycloak_id' => 'required|string|unique:users,keycloak_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'department' => 'nullable|string|max:100',
        ];
    }
}
