<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventorySerialNumberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'     => 'required|integer',
            'product_id'    => 'required|integer',
            'variation_id'  => 'nullable|integer',
            'batch_id'      => 'nullable|integer',
            'serial_number' => 'required|string|max:255',
            'location_id'   => 'nullable|integer',
            'status'        => 'string|in:available,reserved,sold,returned,damaged,scrapped,in_transit',
            'purchase_price'=> 'nullable|numeric|min:0',
            'currency'      => 'string|size:3',
            'purchased_at'  => 'nullable|date',
            'notes'         => 'nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
