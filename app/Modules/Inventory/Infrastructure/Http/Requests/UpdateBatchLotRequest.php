<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateBatchLotRequest",
 *   @OA\Property(property="lot_number", type="string", nullable=true, maxLength=100),
 *   @OA\Property(property="manufacture_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="remaining_quantity", type="number"),
 *   @OA\Property(property="supplier_batch", type="string", nullable=true, maxLength=100),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class UpdateBatchLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lot_number'         => ['nullable', 'string', 'max:100'],
            'manufacture_date'   => ['nullable', 'date'],
            'expiry_date'        => ['nullable', 'date'],
            'remaining_quantity' => ['sometimes', 'numeric', 'min:0'],
            'supplier_batch'     => ['nullable', 'string', 'max:100'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
