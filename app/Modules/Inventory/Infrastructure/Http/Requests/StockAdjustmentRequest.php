<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Domain\ValueObjects\MovementType;

/**
 * @OA\Schema(
 *   schema="StockAdjustmentRequest",
 *   required={"product_id","location_id","quantity","movement_type"},
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="location_id", type="integer", example=1),
 *   @OA\Property(property="variant_id", type="integer", nullable=true),
 *   @OA\Property(property="batch_lot_id", type="integer", nullable=true),
 *   @OA\Property(property="serial_number_id", type="integer", nullable=true),
 *   @OA\Property(property="quantity", type="number", example=10),
 *   @OA\Property(property="movement_type", type="string", enum={"receipt","issue","transfer","adjustment","return_in","return_out","scrap"}),
 *   @OA\Property(property="unit_cost", type="number", nullable=true),
 *   @OA\Property(property="reference", type="string", nullable=true, maxLength=255),
 *   @OA\Property(property="notes", type="string", nullable=true),
 * )
 */
final class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'location_id'      => ['required', 'integer'],
            'variant_id'       => ['nullable', 'integer'],
            'batch_lot_id'     => ['nullable', 'integer'],
            'serial_number_id' => ['nullable', 'integer'],
            'quantity'         => ['required', 'numeric'],
            'movement_type'    => ['required', 'string', 'in:' . implode(',', MovementType::ALL)],
            'unit_cost'        => ['nullable', 'numeric', 'min:0'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
