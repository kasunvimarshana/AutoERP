<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\SalesCreditNoteData;

final class StoreSalesCreditNoteRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'credit_note_number' => ['nullable', 'string', 'max:100'],
            'credit_note_date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'sales_return_id' => ['nullable', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'reason' => ['required', 'string'],
        ]);
    }

    public function toData(): SalesCreditNoteData
    {
        return new SalesCreditNoteData(
            tenantId: $this->tenantId(),
            creditNoteDate: (string) $this->input('credit_note_date'),
            customerId: (int) $this->input('customer_id'),
            amount: (string) $this->input('amount'),
            organizationUnitId: $this->organizationUnitId(),
            creditNoteNumber: $this->stringOrNull('credit_note_number'),
            salesReturnId: $this->intOrNull('sales_return_id'),
            reason: (string) $this->input('reason'),
        );
    }
}
