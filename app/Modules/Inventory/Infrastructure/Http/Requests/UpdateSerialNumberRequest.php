<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateSerialNumberRequest",
 *   @OA\Property(property="status", type="string", enum={"available","reserved","sold","scrapped"}),
 *   @OA\Property(property="location_id", type="integer", nullable=true),
 *   @OA\Property(property="manufacture_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class UpdateSerialNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'           => ['sometimes', 'string', 'in:available,reserved,sold,scrapped'],
            'location_id'      => ['nullable', 'integer'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date'      => ['nullable', 'date'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
