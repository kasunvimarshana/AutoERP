<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'             => 'required|integer',
            'reference_number'      => 'required|string|max:100',
            'warehouse_id'          => 'required|integer',
            'customer_id'           => 'required|integer',
            'dispatch_date'         => 'required|date',
            'sales_order_id'        => 'nullable|integer',
            'customer_reference'    => 'nullable|string|max:100',
            'estimated_delivery_date'=> 'nullable|date',
            'carrier'               => 'nullable|string|max:100',
            'notes'                 => 'nullable|string',
            'metadata'              => 'nullable|array',
            'status'                => 'string|in:draft,confirmed,in_transit,delivered,cancelled',
            'currency'              => 'string|size:3',
            'total_weight'          => 'nullable|numeric|min:0',
        ];
    }
}
