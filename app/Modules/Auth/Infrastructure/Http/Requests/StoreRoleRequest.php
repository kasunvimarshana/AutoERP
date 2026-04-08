<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="StoreRoleRequest",
 *     required={"name","slug"},
 *     @OA\Property(property="name", type="string", maxLength=100),
 *     @OA\Property(property="slug", type="string", maxLength=100),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="guard_name", type="string", default="api"),
 *     @OA\Property(property="metadata", type="object", nullable=true)
 * )
 */
final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'guard_name'  => ['sometimes', 'string', 'max:50'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
