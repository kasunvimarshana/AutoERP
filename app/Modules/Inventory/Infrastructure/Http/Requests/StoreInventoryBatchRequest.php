<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'         => 'required|integer',
            'product_id'        => 'required|integer',
            'variation_id'      => 'nullable|integer',
            'batch_number'      => 'required|string|max:100',
            'lot_number'        => 'nullable|string|max:100',
            'manufacture_date'  => 'nullable|date',
            'expiry_date'       => 'nullable|date',
            'best_before_date'  => 'nullable|date',
            'supplier_id'       => 'nullable|integer',
            'supplier_batch_ref'=> 'nullable|string|max:255',
            'initial_qty'       => 'numeric|min:0',
            'unit_cost'         => 'numeric|min:0',
            'currency'          => 'string|size:3',
            'status'            => 'string|in:active,quarantine,expired,depleted,recalled',
            'notes'             => 'nullable|string',
            'metadata'          => 'nullable|array',
        ];
    }
}
