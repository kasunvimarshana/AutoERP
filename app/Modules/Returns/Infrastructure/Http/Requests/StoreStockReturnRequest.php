<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'              => 'required|integer',
            'reference_number'       => 'required|string|max:100',
            'return_type'            => 'required|string|in:purchase_return,sales_return',
            'party_id'               => 'required|integer',
            'party_type'             => 'required|string|in:supplier,customer',
            'original_reference_id'  => 'nullable|integer',
            'original_reference_type'=> 'nullable|string|max:100',
            'return_reason'          => 'nullable|string|max:255',
            'total_amount'           => 'numeric|min:0',
            'currency'               => 'string|size:3',
            'restock'                => 'boolean',
            'restock_location_id'    => 'nullable|integer',
            'restocking_fee'         => 'numeric|min:0',
            'notes'                  => 'nullable|string',
            'metadata'               => 'nullable|array',
            'status'                 => 'string|in:draft,pending_inspection,approved,rejected,completed,cancelled',
        ];
    }
}
