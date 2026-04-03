<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCycleCountLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'      => 'required|integer',
            'cycle_count_id' => 'required|integer',
            'product_id'     => 'required|integer',
            'variation_id'   => 'nullable|integer',
            'batch_id'       => 'nullable|integer',
            'serial_number_id'=> 'nullable|integer',
            'location_id'    => 'nullable|integer',
            'expected_qty'   => 'numeric|min:0',
            'notes'          => 'nullable|string',
        ];
    }
}
