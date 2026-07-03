<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Purchase\DTOs\CreatePurchaseDebitNoteData;

final class StorePurchaseDebitNoteRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'debit_note_date' => ['required', 'date'],
            'debit_note_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'reason' => ['required', 'string'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
        ]);
    }

    public function toData(): CreatePurchaseDebitNoteData
    {
        return new CreatePurchaseDebitNoteData(
            tenantId: $this->tenantId(),
            debitNoteDate: (string) $this->input('debit_note_date'),
            amount: (string) $this->input('amount'),
            organizationUnitId: $this->organizationUnitId(),
            debitNoteNumber: $this->filled('debit_note_number') ? (string) $this->input('debit_note_number') : null,
            supplierType: $this->filled('supplier_type') ? (string) $this->input('supplier_type') : 'supplier',
            supplierId: (int) $this->input('supplier_id'),
            reason: (string) $this->input('reason'),
        );
    }
}
