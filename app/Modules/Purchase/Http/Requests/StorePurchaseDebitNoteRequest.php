<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\DTOs\PurchaseDebitNoteData;

final class StorePurchaseDebitNoteRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'debit_note_date' => ['required', 'date'],
            'debit_note_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'reason' => ['required', 'string'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(): PurchaseDebitNoteData
    {
        return new PurchaseDebitNoteData(
            tenantId: $this->tenantId(),
            debitNoteDate: (string) $this->input('debit_note_date'),
            amount: (string) $this->input('amount'),
            organizationUnitId: $this->organizationUnitId(),
            debitNoteNumber: $this->filled('debit_note_number') ? (string) $this->input('debit_note_number') : null,
            supplierType: $this->filled('supplier_type') ? (string) $this->input('supplier_type') : 'supplier',
            supplierId: (int) $this->input('supplier_id'),
            sourceType: $this->filled('source_type') ? (string) $this->input('source_type') : 'supplier_debit_note_only',
            sourceId: $this->filled('source_id') ? (int) $this->input('source_id') : null,
            reason: (string) $this->input('reason'),
        );
    }
}
