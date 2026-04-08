<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateOrderRequest",
 *   @OA\Property(property="supplier_id", type="integer", nullable=true),
 *   @OA\Property(property="customer_id", type="integer", nullable=true),
 *   @OA\Property(property="warehouse_id", type="integer", nullable=true),
 *   @OA\Property(property="order_date", type="string", format="date"),
 *   @OA\Property(property="expected_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="currency", type="string", minLength=3, maxLength=3),
 *   @OA\Property(property="exchange_rate", type="number", format="float"),
 *   @OA\Property(property="billing_address", type="object", nullable=true),
 *   @OA\Property(property="shipping_address", type="object", nullable=true),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="internal_notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/OrderLineInput")),
 * )
 */
final class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'               => ['nullable', 'integer', 'min:1'],
            'customer_id'               => ['nullable', 'integer', 'min:1'],
            'warehouse_id'              => ['nullable', 'integer', 'min:1'],
            'order_date'                => ['sometimes', 'date'],
            'expected_date'             => ['nullable', 'date'],
            'currency'                  => ['nullable', 'string', 'size:3'],
            'exchange_rate'             => ['nullable', 'numeric', 'min:0'],
            'billing_address'           => ['nullable', 'array'],
            'shipping_address'          => ['nullable', 'array'],
            'notes'                     => ['nullable', 'string'],
            'internal_notes'            => ['nullable', 'string'],
            'metadata'                  => ['nullable', 'array'],
            'lines'                     => ['sometimes', 'array'],
            'lines.*.product_id'        => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id'        => ['nullable', 'integer', 'min:1'],
            'lines.*.description'       => ['nullable', 'string', 'max:500'],
            'lines.*.quantity'          => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_of_measure'   => ['nullable', 'string', 'max:50'],
            'lines.*.unit_price'        => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes'             => ['nullable', 'string'],
            'lines.*.metadata'          => ['nullable', 'array'],
        ];
    }
}
