<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrnRequest extends FormRequest
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
            'grn_number' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer'],
            'created_by' => ['required', 'integer'],
            'purchase_order_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string'],
        ];
    }
}
