<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'email'       => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'first_name'  => ['sometimes', 'string', 'max:100'],
            'last_name'   => ['sometimes', 'string', 'max:100'],
            'username'    => ['sometimes', 'string', 'max:100', "unique:users,username,{$userId}"],
            'keycloak_id' => ['sometimes', 'nullable', 'string', 'max:255', "unique:users,keycloak_id,{$userId}"],
            'roles'       => ['sometimes', 'array'],
            'roles.*'     => ['string', 'max:100'],
            'is_active'   => ['sometimes', 'boolean'],
            'phone'       => ['sometimes', 'nullable', 'string', 'max:30'],
            'department'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'avatar_url'  => ['sometimes', 'nullable', 'url', 'max:500'],
            'preferences' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'        => 'Please provide a valid email address.',
            'email.unique'       => 'A user with this email already exists.',
            'username.unique'    => 'A user with this username already exists.',
            'keycloak_id.unique' => 'A user with this Keycloak ID already exists.',
            'avatar_url.url'     => 'Avatar URL must be a valid URL.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
