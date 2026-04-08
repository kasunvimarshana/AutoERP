<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesOrderLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity'              => 'sometimes|numeric|min:0',
            'unit_price'            => 'sometimes|numeric|min:0',
            'tax_rate'              => 'sometimes|numeric|min:0',
            'discount_amount'       => 'sometimes|numeric|min:0',
            'total_amount'          => 'sometimes|numeric|min:0',
            'warehouse_location_id' => 'sometimes|nullable|integer',
            'batch_number'          => 'sometimes|nullable|string|max:100',
            'serial_number'         => 'sometimes|nullable|string|max:100',
            'description'           => 'sometimes|nullable|string',
            'notes'                 => 'sometimes|nullable|string',
            'metadata'              => 'nullable|array',
        ];
    }
}
