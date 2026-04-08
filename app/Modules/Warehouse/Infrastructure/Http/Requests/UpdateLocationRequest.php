<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Warehouse\Domain\ValueObjects\LocationType;

/**
 * @OA\Schema(
 *   schema="UpdateLocationRequest",
 *   @OA\Property(property="warehouse_id", type="integer", nullable=true),
 *   @OA\Property(property="parent_id", type="integer", nullable=true),
 *   @OA\Property(property="name", type="string", maxLength=255, nullable=true),
 *   @OA\Property(property="code", type="string", maxLength=100, nullable=true),
 *   @OA\Property(property="type", type="string", enum={"internal","customer","supplier","virtual","transit"}, nullable=true),
 *   @OA\Property(property="capacity", type="number", format="float", nullable=true),
 *   @OA\Property(property="is_active", type="boolean", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'parent_id'    => ['nullable', 'integer', 'exists:locations,id'],
            'name'         => ['sometimes', 'string', 'max:255'],
            'code'         => ['sometimes', 'string', 'max:100'],
            'type'         => ['sometimes', 'string', 'in:' . implode(',', LocationType::ALL)],
            'capacity'     => ['nullable', 'numeric', 'min:0'],
            'is_active'    => ['boolean'],
            'metadata'     => ['nullable', 'array'],
        ];
    }
}
