<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'            => 'required|integer',
            'dispatch_id'          => 'required|integer',
            'product_id'           => 'required|integer',
            'quantity'             => 'required|numeric|min:0',
            'sales_order_line_id'  => 'nullable|integer',
            'product_variant_id'   => 'nullable|integer',
            'description'          => 'nullable|string',
            'unit_of_measure'      => 'nullable|string|max:50',
            'warehouse_location_id'=> 'nullable|integer',
            'batch_number'         => 'nullable|string|max:100',
            'serial_number'        => 'nullable|string|max:100',
            'status'               => 'string|in:pending,picked,packed,shipped,cancelled',
            'weight'               => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
            'metadata'             => 'nullable|array',
        ];
    }
}
