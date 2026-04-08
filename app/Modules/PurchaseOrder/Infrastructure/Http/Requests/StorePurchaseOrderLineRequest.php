<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'         => 'required|integer',
            'purchase_order_id' => 'required|integer',
            'line_number'       => 'required|integer',
            'product_id'        => 'required|integer',
            'quantity_ordered'  => 'required|numeric|min:0',
            'unit_price'        => 'required|numeric|min:0',
            'variation_id'      => 'nullable|integer',
            'description'       => 'nullable|string|max:255',
            'uom_id'            => 'nullable|integer',
            'discount_percent'  => 'numeric|min:0|max:100',
            'tax_percent'       => 'numeric|min:0|max:100',
            'line_total'        => 'numeric|min:0',
            'expected_date'     => 'nullable|date',
            'notes'             => 'nullable|string',
            'metadata'          => 'nullable|array',
            'status'            => 'string|in:open,partially_received,fully_received,cancelled',
        ];
    }
}
