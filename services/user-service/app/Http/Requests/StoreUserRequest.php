<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'username'    => ['required', 'string', 'max:100', 'unique:users,username'],
            'keycloak_id' => ['nullable', 'string', 'max:255', 'unique:users,keycloak_id'],
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'department'  => ['nullable', 'string', 'max:100'],
            'avatar_url'  => ['nullable', 'url', 'max:500'],
            'preferences' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'       => 'Email address is required.',
            'email.email'          => 'Please provide a valid email address.',
            'email.unique'         => 'A user with this email already exists.',
            'first_name.required'  => 'First name is required.',
            'last_name.required'   => 'Last name is required.',
            'username.required'    => 'Username is required.',
            'username.unique'      => 'A user with this username already exists.',
            'keycloak_id.unique'   => 'A user with this Keycloak ID already exists.',
            'avatar_url.url'       => 'Avatar URL must be a valid URL.',
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
