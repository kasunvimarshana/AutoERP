<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'             => 'required|integer',
            'sales_order_id'        => 'required|integer',
            'product_id'            => 'required|integer',
            'quantity'              => 'required|numeric|min:0',
            'unit_price'            => 'required|numeric|min:0',
            'product_variant_id'    => 'nullable|integer',
            'description'           => 'nullable|string',
            'tax_rate'              => 'numeric|min:0',
            'discount_amount'       => 'numeric|min:0',
            'total_amount'          => 'numeric|min:0',
            'unit_of_measure'       => 'nullable|string|max:50',
            'status'                => 'string|in:pending,picking,packed,dispatched,cancelled',
            'warehouse_location_id' => 'nullable|integer',
            'batch_number'          => 'nullable|string|max:100',
            'serial_number'         => 'nullable|string|max:100',
            'notes'                 => 'nullable|string',
            'metadata'              => 'nullable|array',
        ];
    }
}
