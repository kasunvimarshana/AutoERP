<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrnRequest extends FormRequest
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
            'grn_number' => ['nullable', 'string', 'max:255'],
            'received_date' => ['nullable', 'date'],
            'currency_id' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer'],
            'purchase_order_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string'],
        ];
    }
}
