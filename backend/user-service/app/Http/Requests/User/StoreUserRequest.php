<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'roles' => 'sometimes|array',
            'roles.*' => ['string', new Enum(UserRole::class)],
            'attributes' => 'sometimes|array',
            'attributes.department' => 'sometimes|string',
            'attributes.region' => 'sometimes|string',
            'attributes.clearance_level' => 'sometimes|integer|min:1|max:5',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
