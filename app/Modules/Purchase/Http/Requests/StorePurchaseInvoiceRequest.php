<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;

final class StorePurchaseInvoiceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'invoice_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.source_type' => ['required', 'in:goods_receipt_note,purchase_order'],
            'sources.*.source_id' => ['required', 'integer', 'min:1'],
            'sources.*.line_quantities' => ['nullable', 'array'],
            'sources.*.line_quantities.*' => ['decimal:0,6', 'gt:0'],
        ];
    }

    public function toData(): CreatePurchaseInvoiceData
    {
        return new CreatePurchaseInvoiceData(
            tenantId: $this->tenantId(),
            invoiceDate: (string) $this->input('invoice_date'),
            organizationUnitId: $this->organizationUnitId(),
            invoiceNumber: $this->filled('invoice_number') ? (string) $this->input('invoice_number') : null,
            supplierType: $this->filled('supplier_type') ? (string) $this->input('supplier_type') : null,
            supplierId: $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            dueDate: $this->filled('due_date') ? (string) $this->input('due_date') : null,
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            createdBy: $this->currentUserId(),
            sources: array_map(static fn (array $row): PurchaseInvoiceSourceData => new PurchaseInvoiceSourceData(
                sourceType: (string) $row['source_type'],
                sourceId: (int) $row['source_id'],
                lineQuantities: array_map('strval', $row['line_quantities'] ?? []),
            ), $this->input('sources')),
        );
    }
}
