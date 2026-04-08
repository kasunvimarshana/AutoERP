<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'          => 'required|integer',
            'reference_number'   => 'required|string|max:100',
            'supplier_id'        => 'required|integer',
            'order_date'         => 'required|date',
            'supplier_reference' => 'nullable|string|max:100',
            'expected_date'      => 'nullable|date',
            'warehouse_id'       => 'nullable|integer',
            'currency'           => 'string|size:3',
            'subtotal'           => 'numeric|min:0',
            'tax_amount'         => 'numeric|min:0',
            'discount_amount'    => 'numeric|min:0',
            'total_amount'       => 'numeric|min:0',
            'notes'              => 'nullable|string',
            'metadata'           => 'nullable|array',
            'status'             => 'string|in:draft,submitted,approved,partially_received,fully_received,cancelled,closed',
        ];
    }
}
