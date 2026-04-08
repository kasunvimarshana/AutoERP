<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreReturnRequest",
 *   required={"type","return_date","reason"},
 *   @OA\Property(property="type", type="string", enum={"purchase_return","sale_return"}, example="purchase_return"),
 *   @OA\Property(property="original_order_id", type="integer", nullable=true, example=1),
 *   @OA\Property(property="supplier_id", type="integer", nullable=true),
 *   @OA\Property(property="customer_id", type="integer", nullable=true),
 *   @OA\Property(property="warehouse_id", type="integer", nullable=true),
 *   @OA\Property(property="return_date", type="string", format="date", example="2026-04-05"),
 *   @OA\Property(property="reason", type="string", enum={"defective","wrong_item","damaged","overdelivery","quality_issue","other"}),
 *   @OA\Property(property="restock_location_id", type="integer", nullable=true),
 *   @OA\Property(property="fee_amount", type="number", format="float", example=0),
 *   @OA\Property(property="fee_description", type="string", nullable=true),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="internal_notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/ReturnLineInput")),
 * )
 * @OA\Schema(
 *   schema="ReturnLineInput",
 *   required={"product_id","quantity_requested","unit_price"},
 *   @OA\Property(property="order_line_id", type="integer", nullable=true),
 *   @OA\Property(property="product_id", type="integer", example=1),
 *   @OA\Property(property="variant_id", type="integer", nullable=true),
 *   @OA\Property(property="quantity_requested", type="number", format="float", example=5.0),
 *   @OA\Property(property="unit_price", type="number", format="float", example=25.50),
 *   @OA\Property(property="quality_check_result", type="string", enum={"passed","failed","pending","quarantine"}),
 *   @OA\Property(property="restock_action", type="string", enum={"restock","scrap","quarantine","return_to_supplier"}),
 *   @OA\Property(property="quality_notes", type="string", nullable=true),
 *   @OA\Property(property="condition_notes", type="string", nullable=true),
 * )
 */
final class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'                              => ['required', 'string', 'in:purchase_return,sale_return'],
            'original_order_id'                 => ['nullable', 'integer', 'min:1'],
            'supplier_id'                       => ['nullable', 'integer', 'min:1'],
            'customer_id'                       => ['nullable', 'integer', 'min:1'],
            'warehouse_id'                      => ['nullable', 'integer', 'min:1'],
            'return_date'                       => ['required', 'date'],
            'reason'                            => ['required', 'string', 'in:defective,wrong_item,damaged,overdelivery,quality_issue,other'],
            'restock_location_id'               => ['nullable', 'integer', 'min:1'],
            'fee_amount'                        => ['nullable', 'numeric', 'min:0'],
            'fee_description'                   => ['nullable', 'string', 'max:255'],
            'notes'                             => ['nullable', 'string'],
            'internal_notes'                    => ['nullable', 'string'],
            'metadata'                          => ['nullable', 'array'],
            'lines'                             => ['sometimes', 'array'],
            'lines.*.order_line_id'             => ['nullable', 'integer', 'min:1'],
            'lines.*.product_id'                => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id'                => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_lot_id'              => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_number_id'          => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity_requested'        => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_price'                => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.quality_check_result'      => ['nullable', 'string', 'in:passed,failed,pending,quarantine'],
            'lines.*.quality_notes'             => ['nullable', 'string'],
            'lines.*.condition_notes'           => ['nullable', 'string'],
            'lines.*.restock_action'            => ['nullable', 'string', 'in:restock,scrap,quarantine,return_to_supplier'],
        ];
    }
}
