<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'          => 'required|integer',
            'reference_number'   => 'required|string|max:100',
            'customer_id'        => 'required|integer',
            'order_date'         => 'required|date',
            'customer_reference' => 'nullable|string|max:100',
            'required_date'      => 'nullable|date',
            'warehouse_id'       => 'nullable|integer',
            'currency'           => 'string|size:3',
            'subtotal'           => 'numeric|min:0',
            'tax_amount'         => 'numeric|min:0',
            'discount_amount'    => 'numeric|min:0',
            'total_amount'       => 'numeric|min:0',
            'shipping_address'   => 'nullable|array',
            'notes'              => 'nullable|string',
            'metadata'           => 'nullable|array',
            'status'             => 'string|in:draft,confirmed,picking,packing,shipped,delivered,cancelled',
        ];
    }
}
