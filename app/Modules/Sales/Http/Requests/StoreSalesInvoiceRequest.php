<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\CreateSalesInvoiceData;
use Modules\Sales\DTOs\SalesInvoiceSourceData;

final class StoreSalesInvoiceRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'invoice_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.source_type' => ['required', 'in:sales_delivery,sales_order'],
            'sources.*.source_id' => ['required', 'integer', 'min:1'],
            'sources.*.line_quantities' => ['nullable', 'array'],
            'sources.*.line_quantities.*' => ['decimal:0,6', 'gt:0'],
        ]);
    }

    public function toData(): CreateSalesInvoiceData
    {
        return new CreateSalesInvoiceData(
            tenantId: $this->tenantId(),
            invoiceDate: (string) $this->input('invoice_date'),
            organizationUnitId: $this->organizationUnitId(),
            invoiceNumber: $this->stringOrNull('invoice_number'),
            customerId: $this->intOrNull('customer_id'),
            dueDate: $this->stringOrNull('due_date'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            sources: array_map(static fn (array $row): SalesInvoiceSourceData => new SalesInvoiceSourceData(
                sourceType: (string) $row['source_type'],
                sourceId: (int) $row['source_id'],
                lineQuantities: array_map('strval', $row['line_quantities'] ?? []),
            ), $this->input('sources', [])),
        );
    }
}
