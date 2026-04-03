<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'         => 'sometimes|nullable|string|max:100',
            'name'         => 'sometimes|required|string|max:255',
            'type'         => 'sometimes|string|in:bin,rack,shelf,floor,receiving,shipping,staging,quarantine',
            'aisle'        => 'nullable|string|max:50',
            'row'          => 'nullable|string|max:50',
            'level'        => 'nullable|string|max:50',
            'bin'          => 'nullable|string|max:50',
            'capacity'     => 'nullable|numeric|min:0',
            'weight_limit' => 'nullable|numeric|min:0',
            'barcode'      => 'nullable|string|max:255',
            'qr_code'      => 'nullable|string|max:255',
            'is_pickable'  => 'sometimes|boolean',
            'is_storable'  => 'sometimes|boolean',
            'is_packing'   => 'sometimes|boolean',
            'is_active'    => 'sometimes|boolean',
            'metadata'     => 'nullable|array',
        ];
    }
}
