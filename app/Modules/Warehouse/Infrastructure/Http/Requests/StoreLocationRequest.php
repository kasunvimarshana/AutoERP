<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Warehouse\Domain\ValueObjects\LocationType;

/**
 * @OA\Schema(
 *   schema="StoreLocationRequest",
 *   required={"warehouse_id","name","code","type"},
 *   @OA\Property(property="warehouse_id", type="integer", example=1),
 *   @OA\Property(property="parent_id", type="integer", nullable=true),
 *   @OA\Property(property="name", type="string", maxLength=255, example="Aisle A"),
 *   @OA\Property(property="code", type="string", maxLength=100, example="LOC-A01"),
 *   @OA\Property(property="type", type="string", enum={"internal","customer","supplier","virtual","transit"}),
 *   @OA\Property(property="capacity", type="number", format="float", nullable=true),
 *   @OA\Property(property="is_active", type="boolean", example=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'parent_id'    => ['nullable', 'integer', 'exists:locations,id'],
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['required', 'string', 'max:100'],
            'type'         => ['required', 'string', 'in:' . implode(',', LocationType::ALL)],
            'capacity'     => ['nullable', 'numeric', 'min:0'],
            'is_active'    => ['boolean'],
            'metadata'     => ['nullable', 'array'],
        ];
    }
}
