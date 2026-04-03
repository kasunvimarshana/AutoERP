<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySerialNumberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'location_id'   => 'sometimes|nullable|integer',
            'status'        => 'sometimes|string|in:available,reserved,sold,returned,damaged,scrapped,in_transit',
            'purchase_price'=> 'sometimes|nullable|numeric|min:0',
            'currency'      => 'sometimes|string|size:3',
            'purchased_at'  => 'sometimes|nullable|date',
            'sold_at'       => 'sometimes|nullable|date',
            'returned_at'   => 'sometimes|nullable|date',
            'notes'         => 'sometimes|nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
