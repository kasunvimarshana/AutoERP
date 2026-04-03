<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'    => 'required|integer',
            'warehouse_id' => 'required|integer',
            'zone_id'      => 'nullable|integer',
            'code'         => 'nullable|string|max:100',
            'name'         => 'required|string|max:255',
            'type'         => 'string|in:bin,rack,shelf,floor,receiving,shipping,staging,quarantine',
            'aisle'        => 'nullable|string|max:50',
            'row'          => 'nullable|string|max:50',
            'level'        => 'nullable|string|max:50',
            'bin'          => 'nullable|string|max:50',
            'capacity'     => 'nullable|numeric|min:0',
            'weight_limit' => 'nullable|numeric|min:0',
            'barcode'      => 'nullable|string|max:255',
            'qr_code'      => 'nullable|string|max:255',
            'is_pickable'  => 'boolean',
            'is_storable'  => 'boolean',
            'is_packing'   => 'boolean',
            'is_active'    => 'boolean',
            'metadata'     => 'nullable|array',
        ];
    }
}
