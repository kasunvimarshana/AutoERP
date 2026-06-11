<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\CreateSalesQuotationData;

class StoreSalesQuotationRequest extends SalesDocumentRequest
{
    public function rules(): array
    {
        return array_merge($this->documentRules('quotation_date'), [
            'quotation_number' => ['nullable', 'string', 'max:100'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
        ]);
    }

    public function toData(): CreateSalesQuotationData
    {
        return new CreateSalesQuotationData(
            tenantId: $this->tenantId(),
            quotationDate: (string) $this->input('quotation_date'),
            customerId: (int) $this->input('customer_id'),
            organizationUnitId: $this->organizationUnitId(),
            quotationNumber: $this->stringOrNull('quotation_number'),
            validUntil: $this->stringOrNull('valid_until'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: $this->lineData(),
            adjustments: $this->adjustmentData(),
        );
    }
}
