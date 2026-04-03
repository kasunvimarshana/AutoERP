<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockReturnLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'          => 'required|integer',
            'stock_return_id'    => 'required|integer',
            'product_id'         => 'required|integer',
            'quantity_requested' => 'required|numeric|min:0',
            'variation_id'       => 'nullable|integer',
            'batch_id'           => 'nullable|integer',
            'serial_number_id'   => 'nullable|integer',
            'uom_id'             => 'nullable|integer',
            'unit_price'         => 'nullable|numeric|min:0',
            'unit_cost'          => 'nullable|numeric|min:0',
            'condition'          => 'string|in:good,damaged,defective,expired',
            'disposition'        => 'string|in:restock,scrap,vendor_return,quarantine',
            'notes'              => 'nullable|string',
        ];
    }
}
