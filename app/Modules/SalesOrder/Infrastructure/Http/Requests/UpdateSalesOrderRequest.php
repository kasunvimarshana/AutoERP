<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_reference' => 'sometimes|nullable|string|max:100',
            'required_date'      => 'sometimes|nullable|date',
            'warehouse_id'       => 'sometimes|nullable|integer',
            'shipping_address'   => 'sometimes|nullable|array',
            'notes'              => 'sometimes|nullable|string',
            'metadata'           => 'nullable|array',
        ];
    }
}
