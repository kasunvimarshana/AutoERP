<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreBatchLotRequest",
 *   required={"product_id","batch_number"},
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="batch_number", type="string", maxLength=100, example="BATCH-001"),
 *   @OA\Property(property="lot_number", type="string", nullable=true, maxLength=100),
 *   @OA\Property(property="manufacture_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="initial_quantity", type="number", example=100),
 *   @OA\Property(property="remaining_quantity", type="number", example=100),
 *   @OA\Property(property="supplier_batch", type="string", nullable=true, maxLength=100),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class StoreBatchLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'integer'],
            'batch_number'       => ['required', 'string', 'max:100'],
            'lot_number'         => ['nullable', 'string', 'max:100'],
            'manufacture_date'   => ['nullable', 'date'],
            'expiry_date'        => ['nullable', 'date'],
            'initial_quantity'   => ['numeric', 'min:0'],
            'remaining_quantity' => ['numeric', 'min:0'],
            'supplier_batch'     => ['nullable', 'string', 'max:100'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
