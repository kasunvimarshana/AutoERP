<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity_ordered'  => 'sometimes|numeric|min:0',
            'unit_price'        => 'sometimes|numeric|min:0',
            'discount_percent'  => 'sometimes|numeric|min:0|max:100',
            'tax_percent'       => 'sometimes|numeric|min:0|max:100',
            'line_total'        => 'sometimes|numeric|min:0',
            'expected_date'     => 'sometimes|nullable|date',
            'notes'             => 'sometimes|nullable|string',
            'metadata'          => 'nullable|array',
        ];
    }
}
