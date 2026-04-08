<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StockTransferRequest",
 *   required={"product_id","from_location_id","to_location_id","quantity"},
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="from_location_id", type="integer", example=1),
 *   @OA\Property(property="to_location_id", type="integer", example=2),
 *   @OA\Property(property="variant_id", type="integer", nullable=true),
 *   @OA\Property(property="batch_lot_id", type="integer", nullable=true),
 *   @OA\Property(property="quantity", type="number", example=5),
 *   @OA\Property(property="reference", type="string", nullable=true, maxLength=255),
 *   @OA\Property(property="notes", type="string", nullable=true),
 * )
 */
final class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'from_location_id' => ['required', 'integer'],
            'to_location_id'   => ['required', 'integer', 'different:from_location_id'],
            'variant_id'       => ['nullable', 'integer'],
            'batch_lot_id'     => ['nullable', 'integer'],
            'quantity'         => ['required', 'numeric', 'min:0.001'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
