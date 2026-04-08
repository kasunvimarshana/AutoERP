<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string", minLength=8),
 *     @OA\Property(property="status", type="string", enum={"active","inactive","suspended","pending"}),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="locale", type="string"),
 *     @OA\Property(property="timezone", type="string"),
 *     @OA\Property(property="preferences", type="object"),
 *     @OA\Property(property="metadata", type="object")
 * )
 */
final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => ['sometimes', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password'    => ['sometimes', 'string', Password::min(8)],
            'status'      => ['sometimes', 'string', 'in:active,inactive,suspended,pending'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'locale'      => ['nullable', 'string', 'max:10'],
            'timezone'    => ['nullable', 'string', 'max:50'],
            'avatar_path' => ['nullable', 'string'],
            'preferences' => ['nullable', 'array'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
