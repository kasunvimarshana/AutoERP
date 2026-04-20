<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseReturnRequest extends FormRequest
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
            'return_number' => ['nullable', 'string', 'max:255'],
            'return_date' => ['nullable', 'date'],
            'currency_id' => ['nullable', 'integer'],
            'original_grn_id' => ['nullable', 'integer'],
            'original_invoice_id' => ['nullable', 'integer'],
            'return_reason' => ['nullable', 'string'],
            'exchange_rate' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'string'],
            'tax_total' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'debit_note_number' => ['nullable', 'string'],
            'journal_entry_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
