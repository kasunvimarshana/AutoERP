<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Warehouse\Domain\ValueObjects\WarehouseType;

/**
 * @OA\Schema(
 *   schema="UpdateWarehouseRequest",
 *   @OA\Property(property="name", type="string", maxLength=255, nullable=true),
 *   @OA\Property(property="code", type="string", maxLength=50, nullable=true),
 *   @OA\Property(property="type", type="string", enum={"standard","virtual","transit","external"}, nullable=true),
 *   @OA\Property(property="address", type="object", nullable=true),
 *   @OA\Property(property="is_active", type="boolean", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'code'      => ['sometimes', 'string', 'max:50'],
            'type'      => ['sometimes', 'string', 'in:' . implode(',', WarehouseType::ALL)],
            'address'   => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
