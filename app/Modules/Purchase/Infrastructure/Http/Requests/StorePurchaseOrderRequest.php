<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'supplier_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer'],
            'po_number' => ['required', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'created_by' => ['required', 'integer'],
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
