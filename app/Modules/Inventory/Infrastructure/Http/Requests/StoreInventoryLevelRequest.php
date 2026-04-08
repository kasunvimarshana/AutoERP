<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryLevelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'    => 'required|integer',
            'product_id'   => 'required|integer',
            'variation_id' => 'nullable|integer',
            'location_id'  => 'nullable|integer',
            'batch_id'     => 'nullable|integer',
            'uom_id'       => 'nullable|integer',
            'qty_on_hand'  => 'numeric',
            'qty_reserved' => 'numeric|min:0',
            'qty_on_order' => 'numeric|min:0',
            'reorder_point'=> 'nullable|numeric|min:0',
            'reorder_qty'  => 'nullable|numeric|min:0',
            'max_qty'      => 'nullable|numeric|min:0',
            'min_qty'      => 'nullable|numeric|min:0',
        ];
    }
}
