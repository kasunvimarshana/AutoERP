<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseInvoiceRequest extends FormRequest
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
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_id' => ['nullable', 'integer'],
            'grn_header_id' => ['nullable', 'integer'],
            'purchase_order_id' => ['nullable', 'integer'],
            'supplier_invoice_number' => ['nullable', 'string'],
            'exchange_rate' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'string'],
            'tax_total' => ['nullable', 'string'],
            'discount_total' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'ap_account_id' => ['nullable', 'integer'],
            'journal_entry_id' => ['nullable', 'integer'],
        ];
    }
}
