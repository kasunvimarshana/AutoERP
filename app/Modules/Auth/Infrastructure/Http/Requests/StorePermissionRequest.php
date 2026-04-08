<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="StorePermissionRequest",
 *     required={"name","slug"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="products.view"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="module", type="string", nullable=true, example="Inventory"),
 *     @OA\Property(property="guard_name", type="string", default="api")
 * )
 */
final class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'unique:permissions,slug'],
            'description' => ['nullable', 'string'],
            'module'      => ['nullable', 'string', 'max:100'],
            'guard_name'  => ['sometimes', 'string', 'max:50'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
