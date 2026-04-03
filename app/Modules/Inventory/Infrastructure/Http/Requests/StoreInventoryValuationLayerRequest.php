<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryValuationLayerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer',
            'product_id'      => 'required|integer',
            'variation_id'    => 'nullable|integer',
            'batch_id'        => 'nullable|integer',
            'location_id'     => 'nullable|integer',
            'layer_date'      => 'required|date',
            'qty_in'          => 'required|numeric|min:0',
            'unit_cost'       => 'required|numeric|min:0',
            'currency'        => 'string|size:3',
            'valuation_method'=> 'string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'reference_type'  => 'nullable|string|max:100',
            'reference_id'    => 'nullable|integer',
            'metadata'        => 'nullable|array',
        ];
    }
}
