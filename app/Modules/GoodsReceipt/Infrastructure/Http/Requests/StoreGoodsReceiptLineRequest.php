<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'             => 'required|integer',
            'goods_receipt_id'      => 'required|integer',
            'line_number'           => 'required|integer',
            'product_id'            => 'required|integer',
            'quantity_received'     => 'required|numeric|min:0',
            'unit_cost'             => 'numeric|min:0',
            'purchase_order_line_id'=> 'nullable|integer',
            'variation_id'          => 'nullable|integer',
            'batch_id'              => 'nullable|integer',
            'serial_number'         => 'nullable|string|max:100',
            'uom_id'                => 'nullable|integer',
            'quantity_expected'     => 'numeric|min:0',
            'quantity_accepted'     => 'numeric|min:0',
            'quantity_rejected'     => 'numeric|min:0',
            'condition'             => 'string|in:good,damaged,expired,quarantine',
            'notes'                 => 'nullable|string',
            'metadata'              => 'nullable|array',
            'status'                => 'string|in:pending,accepted,rejected,partially_accepted',
            'putaway_location_id'   => 'nullable|integer',
        ];
    }
}
