<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'         => 'required|integer',
            'reference_number'  => 'required|string|max:100',
            'supplier_id'       => 'required|integer',
            'purchase_order_id' => 'nullable|integer',
            'warehouse_id'      => 'nullable|integer',
            'received_date'     => 'nullable|date',
            'currency'          => 'string|size:3',
            'notes'             => 'nullable|string',
            'metadata'          => 'nullable|array',
            'status'            => 'string|in:draft,pending,partially_received,fully_received,approved,cancelled',
            'received_by'       => 'nullable|integer',
        ];
    }
}
