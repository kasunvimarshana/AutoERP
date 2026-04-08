<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Warehouse\Domain\ValueObjects\WarehouseType;

/**
 * @OA\Schema(
 *   schema="StoreWarehouseRequest",
 *   required={"name","code","type"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="Main Warehouse"),
 *   @OA\Property(property="code", type="string", maxLength=50, example="WH-001"),
 *   @OA\Property(property="type", type="string", enum={"standard","virtual","transit","external"}),
 *   @OA\Property(property="address", type="object", nullable=true),
 *   @OA\Property(property="is_active", type="boolean", example=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'type'      => ['required', 'string', 'in:' . implode(',', WarehouseType::ALL)],
            'address'   => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
