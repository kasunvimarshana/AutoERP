<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_reference' => 'sometimes|nullable|string|max:100',
            'expected_date'      => 'sometimes|nullable|date',
            'warehouse_id'       => 'sometimes|nullable|integer',
            'notes'              => 'sometimes|nullable|string',
            'metadata'           => 'nullable|array',
        ];
    }
}
