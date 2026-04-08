<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * @OA\Schema(
 *     schema="StoreUserRequest",
 *     required={"name","email","password","tenant_id"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string", minLength=8),
 *     @OA\Property(property="tenant_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"active","inactive","suspended","pending"}),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="locale", type="string"),
 *     @OA\Property(property="timezone", type="string")
 * )
 */
final class StoreUserRequest extends FormRequest
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
            'password'  => ['required', 'string', Password::min(8)],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'status'    => ['sometimes', 'string', 'in:active,inactive,suspended,pending'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'locale'    => ['nullable', 'string', 'max:10'],
            'timezone'  => ['nullable', 'string', 'max:50'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
