<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateReturnRequest",
 *   @OA\Property(property="original_order_id", type="integer", nullable=true),
 *   @OA\Property(property="supplier_id", type="integer", nullable=true),
 *   @OA\Property(property="customer_id", type="integer", nullable=true),
 *   @OA\Property(property="warehouse_id", type="integer", nullable=true),
 *   @OA\Property(property="return_date", type="string", format="date"),
 *   @OA\Property(property="reason", type="string", enum={"defective","wrong_item","damaged","overdelivery","quality_issue","other"}),
 *   @OA\Property(property="restock_location_id", type="integer", nullable=true),
 *   @OA\Property(property="fee_amount", type="number", format="float"),
 *   @OA\Property(property="fee_description", type="string", nullable=true),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="internal_notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/ReturnLineInput")),
 * )
 */
final class UpdateReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_order_id'                 => ['nullable', 'integer', 'min:1'],
            'supplier_id'                       => ['nullable', 'integer', 'min:1'],
            'customer_id'                       => ['nullable', 'integer', 'min:1'],
            'warehouse_id'                      => ['nullable', 'integer', 'min:1'],
            'return_date'                       => ['sometimes', 'date'],
            'reason'                            => ['sometimes', 'string', 'in:defective,wrong_item,damaged,overdelivery,quality_issue,other'],
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
