<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     required={"name","email","password"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string", minLength=8),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="locale", type="string", example="en"),
 *     @OA\Property(property="timezone", type="string", example="UTC")
 * )
 */
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'locale'    => ['nullable', 'string', 'max:10'],
            'timezone'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
