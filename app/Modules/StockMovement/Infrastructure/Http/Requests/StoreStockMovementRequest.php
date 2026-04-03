<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'reference_number' => 'required|string|max:100',
            'movement_type'    => 'required|string|in:receipt,issue,transfer,adjustment,return_in,return_out',
            'product_id'       => 'required|integer',
            'quantity'         => 'required|numeric',
            'variation_id'     => 'nullable|integer',
            'from_location_id' => 'nullable|integer',
            'to_location_id'   => 'nullable|integer',
            'batch_id'         => 'nullable|integer',
            'serial_number_id' => 'nullable|integer',
            'uom_id'           => 'nullable|integer',
            'unit_cost'        => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|size:3',
            'reference_type'   => 'nullable|string|max:100',
            'reference_id'     => 'nullable|integer',
            'performed_by'     => 'nullable|integer',
            'movement_date'    => 'nullable|date',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
            'status'           => 'nullable|string|in:draft,confirmed,cancelled',
        ];
    }
}
