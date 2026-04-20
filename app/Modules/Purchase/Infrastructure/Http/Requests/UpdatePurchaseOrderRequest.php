<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'currency_id' => ['nullable', 'integer'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer'],
            'expected_date' => ['nullable', 'date'],
            'org_unit_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'string'],
            'tax_total' => ['nullable', 'string'],
            'discount_total' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'approved_by' => ['nullable', 'integer'],
        ];
    }
}
