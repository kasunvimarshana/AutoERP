<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceType;

final class StoreInvoiceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->headerRules(),
            $this->lineRules(),
            $this->sourceRules(),
            $this->sourceLineRules(),
            $this->adjustmentRules(),
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function headerRules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'invoice_type' => ['required', Rule::enum(InvoiceType::class)],
            'direction' => ['required', Rule::enum(InvoiceDirection::class)],
            'invoice_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function lineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_number' => ['required', 'integer', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.line_type' => ['nullable', Rule::enum(InvoiceLineType::class)],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.line_total' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.source_line_type' => ['nullable', 'string', 'max:150', 'required_with:lines.*.source_line_id'],
            'lines.*.source_line_id' => ['nullable', 'integer', 'min:1', 'required_with:lines.*.source_line_type'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function sourceRules(): array
    {
        return [
            'sources' => ['nullable', 'array'],
            'sources.*.source_type' => ['required', 'string', 'max:150'],
            'sources.*.source_id' => ['required', 'integer', 'min:1'],
            'sources.*.source_document_number' => ['nullable', 'string', 'max:255'],
            'sources.*.source_document_date' => ['nullable', 'date'],
            'sources.*.source_subtotal' => ['nullable', 'decimal:0,6', 'min:0'],
            'sources.*.source_adjustment_total' => ['nullable', 'decimal:0,6'],
            'sources.*.source_grand_total' => ['nullable', 'decimal:0,6', 'min:0'],
            'sources.*.invoiced_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'sources.*.allocated_adjustment_amount' => ['nullable', 'decimal:0,6'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function sourceLineRules(): array
    {
        return [
            'source_lines' => ['nullable', 'array'],
            'source_lines.*.source_type' => ['required', 'string', 'max:150'],
            'source_lines.*.source_id' => ['required', 'integer', 'min:1'],
            'source_lines.*.source_line_type' => ['required', 'string', 'max:150'],
            'source_lines.*.source_line_id' => ['required', 'integer', 'min:1'],
            'source_lines.*.source_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'source_lines.*.invoiced_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'source_lines.*.source_unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'source_lines.*.source_line_total' => ['required', 'decimal:0,6', 'min:0'],
            'source_lines.*.previously_invoiced_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'source_lines.*.invoiced_line_total' => ['nullable', 'decimal:0,6', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function adjustmentRules(): array
    {
        return [
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.name' => ['required', 'string', 'max:255'],
            'adjustments.*.adjustment_type' => ['required', Rule::enum(AdjustmentType::class)],
            'adjustments.*.effect' => ['required', Rule::enum(AdjustmentEffect::class)],
            'adjustments.*.amount' => ['required', 'decimal:0,6', 'min:0'],
            'adjustments.*.source_adjustment_type' => ['nullable', 'string', 'max:150', 'required_with:adjustments.*.source_adjustment_id'],
            'adjustments.*.source_adjustment_id' => ['nullable', 'integer', 'min:1', 'required_with:adjustments.*.source_adjustment_type'],
            'adjustments.*.source_type' => ['nullable', 'string', 'max:150', 'required_with:adjustments.*.source_id'],
            'adjustments.*.source_id' => ['nullable', 'integer', 'min:1', 'required_with:adjustments.*.source_type'],
            'adjustments.*.calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'adjustments.*.rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments.*.source_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments.*.allocation_method' => ['nullable', Rule::enum(AllocationMethod::class)],
            'adjustments.*.is_system_generated' => ['nullable', 'boolean'],
            'adjustments.*.description' => ['nullable', 'string'],
        ];
    }

    public function toData(): CreateInvoiceData
    {
        return new CreateInvoiceData(
            tenantId: $this->tenantId(),
            invoiceType: InvoiceType::from((string) $this->input('invoice_type')),
            direction: InvoiceDirection::from((string) $this->input('direction')),
            invoiceDate: (string) $this->input('invoice_date'),
            organizationUnitId: $this->organizationUnitId(),
            invoiceNumber: $this->stringOrNull('invoice_number'),
            partyType: $this->stringOrNull('party_type'),
            partyId: $this->intOrNull('party_id'),
            dueDate: $this->stringOrNull('due_date'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: array_map($this->mapLine(...), $this->input('lines')),
            sources: array_map($this->mapSource(...), $this->input('sources', [])),
            sourceLines: array_map($this->mapSourceLine(...), $this->input('source_lines', [])),
            adjustments: array_map($this->mapAdjustment(...), $this->input('adjustments', [])),
        );
    }

    private function mapLine(array $row): InvoiceLineData
    {
        return new InvoiceLineData(
            lineNumber: (int) $row['line_number'],
            description: (string) $row['description'],
            quantity: (string) $row['quantity'],
            unitPrice: (string) $row['unit_price'],
            lineType: InvoiceLineType::from((string) ($row['line_type'] ?? InvoiceLineType::Item->value)),
            itemId: isset($row['item_id']) ? (int) $row['item_id'] : null,
            uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
            discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
            taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
            chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
            lineTotal: isset($row['line_total']) ? (string) $row['line_total'] : null,
            sourceLineType: $row['source_line_type'] ?? null,
            sourceLineId: isset($row['source_line_id']) ? (int) $row['source_line_id'] : null,
            metadata: $row['metadata'] ?? null,
        );
    }

    private function mapSource(array $row): InvoiceSourceData
    {
        return new InvoiceSourceData(
            tenantId: $this->tenantId(),
            sourceType: (string) $row['source_type'],
            sourceId: (int) $row['source_id'],
            organizationUnitId: $this->organizationUnitId(),
            sourceDocumentNumber: $row['source_document_number'] ?? null,
            sourceDocumentDate: $row['source_document_date'] ?? null,
            sourceSubtotal: (string) ($row['source_subtotal'] ?? '0.000000'),
            sourceAdjustmentTotal: (string) ($row['source_adjustment_total'] ?? '0.000000'),
            sourceGrandTotal: (string) ($row['source_grand_total'] ?? '0.000000'),
            invoicedAmount: (string) ($row['invoiced_amount'] ?? '0.000000'),
            allocatedAdjustmentAmount: (string) ($row['allocated_adjustment_amount'] ?? '0.000000'),
        );
    }

    private function mapSourceLine(array $row): InvoiceSourceLineData
    {
        return new InvoiceSourceLineData(
            tenantId: $this->tenantId(),
            sourceType: (string) $row['source_type'],
            sourceId: (int) $row['source_id'],
            sourceLineType: (string) $row['source_line_type'],
            sourceLineId: (int) $row['source_line_id'],
            sourceQuantity: (string) $row['source_quantity'],
            invoicedQuantity: (string) $row['invoiced_quantity'],
            sourceUnitPrice: (string) $row['source_unit_price'],
            sourceLineTotal: (string) $row['source_line_total'],
            organizationUnitId: $this->organizationUnitId(),
            previouslyInvoicedQuantity: (string) ($row['previously_invoiced_quantity'] ?? '0.000000'),
            invoicedLineTotal: isset($row['invoiced_line_total']) ? (string) $row['invoiced_line_total'] : null,
        );
    }

    private function mapAdjustment(array $row): InvoiceAdjustmentData
    {
        return new InvoiceAdjustmentData(
            name: (string) $row['name'],
            adjustmentType: AdjustmentType::from((string) $row['adjustment_type']),
            effect: AdjustmentEffect::from((string) $row['effect']),
            amount: (string) $row['amount'],
            sourceAdjustmentType: $row['source_adjustment_type'] ?? null,
            sourceAdjustmentId: isset($row['source_adjustment_id']) ? (int) $row['source_adjustment_id'] : null,
            sourceType: $row['source_type'] ?? null,
            sourceId: isset($row['source_id']) ? (int) $row['source_id'] : null,
            calculationType: (string) ($row['calculation_type'] ?? 'fixed'),
            rate: (string) ($row['rate'] ?? '0.000000'),
            sourceAmount: isset($row['source_amount']) ? (string) $row['source_amount'] : null,
            allocationMethod: AllocationMethod::from((string) ($row['allocation_method'] ?? AllocationMethod::Manual->value)),
            isSystemGenerated: (bool) ($row['is_system_generated'] ?? false),
            description: $row['description'] ?? null,
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
