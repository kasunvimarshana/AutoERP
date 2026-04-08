<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreSerialNumberRequest",
 *   required={"product_id","serial_number"},
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="variant_id", type="integer", nullable=true),
 *   @OA\Property(property="serial_number", type="string", maxLength=255, example="SN-0001"),
 *   @OA\Property(property="status", type="string", enum={"available","reserved","sold","scrapped"}),
 *   @OA\Property(property="location_id", type="integer", nullable=true),
 *   @OA\Property(property="manufacture_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class StoreSerialNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'variant_id'       => ['nullable', 'integer'],
            'serial_number'    => ['required', 'string', 'max:255'],
            'status'           => ['string', 'in:available,reserved,sold,scrapped'],
            'location_id'      => ['nullable', 'integer'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date'      => ['nullable', 'date'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
