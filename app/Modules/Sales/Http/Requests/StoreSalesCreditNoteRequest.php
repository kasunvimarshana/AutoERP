<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Sales\DTOs\SalesCreditNoteData;

final class StoreSalesCreditNoteRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'credit_note_number' => ['nullable', 'string', 'max:100'],
            'credit_note_date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'sales_return_id' => ['nullable', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'reason' => ['required', 'string'],
        ];
    }

    public function toData(): SalesCreditNoteData
    {
        return new SalesCreditNoteData(
            tenantId: $this->tenantId(),
            creditNoteDate: (string) $this->input('credit_note_date'),
            customerId: (int) $this->input('customer_id'),
            amount: (string) $this->input('amount'),
            organizationUnitId: $this->organizationUnitId(),
            creditNoteNumber: $this->filled('credit_note_number') ? (string) $this->input('credit_note_number') : null,
            salesReturnId: $this->filled('sales_return_id') ? (int) $this->input('sales_return_id') : null,
            reason: (string) $this->input('reason'),
        );
    }
}
