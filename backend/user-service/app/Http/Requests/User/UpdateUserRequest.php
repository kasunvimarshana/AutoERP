<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'roles' => 'sometimes|array',
            'roles.*' => ['string', new Enum(UserRole::class)],
            'attributes' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
