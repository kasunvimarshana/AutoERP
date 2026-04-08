<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreOrderRequest",
 *   required={"type","order_date"},
 *   @OA\Property(property="type", type="string", enum={"purchase","sale"}, example="purchase"),
 *   @OA\Property(property="supplier_id", type="integer", nullable=true, example=1),
 *   @OA\Property(property="customer_id", type="integer", nullable=true, example=null),
 *   @OA\Property(property="warehouse_id", type="integer", nullable=true, example=1),
 *   @OA\Property(property="order_date", type="string", format="date", example="2026-04-05"),
 *   @OA\Property(property="expected_date", type="string", format="date", nullable=true),
 *   @OA\Property(property="currency", type="string", minLength=3, maxLength=3, example="USD"),
 *   @OA\Property(property="exchange_rate", type="number", format="float", example=1.0),
 *   @OA\Property(property="billing_address", type="object", nullable=true),
 *   @OA\Property(property="shipping_address", type="object", nullable=true),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="internal_notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/OrderLineInput")),
 * )
 * @OA\Schema(
 *   schema="OrderLineInput",
 *   required={"product_id","quantity","unit_price"},
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="variant_id", type="integer", nullable=true),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="quantity", type="number", format="float", example=10.0),
 *   @OA\Property(property="unit_of_measure", type="string", nullable=true, example="kg"),
 *   @OA\Property(property="unit_price", type="number", format="float", example=25.50),
 *   @OA\Property(property="discount_percent", type="number", format="float", example=0),
 *   @OA\Property(property="tax_rate", type="number", format="float", example=10),
 *   @OA\Property(property="notes", type="string", nullable=true),
 * )
 */
final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'                      => ['required', 'string', 'in:purchase,sale'],
            'supplier_id'               => ['nullable', 'integer', 'min:1'],
            'customer_id'               => ['nullable', 'integer', 'min:1'],
            'warehouse_id'              => ['nullable', 'integer', 'min:1'],
            'order_date'                => ['required', 'date'],
            'expected_date'             => ['nullable', 'date'],
            'currency'                  => ['sometimes', 'string', 'size:3'],
            'exchange_rate'             => ['sometimes', 'numeric', 'min:0'],
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
