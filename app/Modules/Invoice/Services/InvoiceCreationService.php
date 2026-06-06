<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Validators\InvoiceValidationService;

final class InvoiceCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceValidationService $validator,
        private readonly InvoiceNumberService $numbers,
        private readonly InvoiceCalculationService $calculations,
        private readonly InvoiceSourceAllocationService $sourceAllocations,
        private readonly InvoiceAdjustmentAllocationService $adjustmentAllocations,
        private readonly InvoiceSourceService $sources,
        private readonly InvoiceAdjustmentService $adjustments,
        private readonly InvoiceBalanceService $balances,
    ) {}

    public function create(CreateInvoiceData $data): Invoice
    {
        $this->validator->validateForCreation($data);

        return DB::transaction(function () use ($data): Invoice {
            $sourceLineRows = $this->sourceAllocations->prepareSourceLineAllocations($data);
            $preparedAdjustments = $this->adjustmentAllocations->prepareAdjustmentAllocations($data, $sourceLineRows);
            $calculationData = $this->withAllocatedAdjustments($data, $preparedAdjustments);
            $calculation = $this->calculations->calculate($calculationData);

            $invoice = Invoice::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_number' => $this->numbers->resolve($data),
                'invoice_type' => $data->invoiceType->value,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => $data->status->value,
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'adjustment_total' => $calculation->adjustmentTotal,
                'grand_total' => $calculation->grandTotal,
                'paid_total' => '0.000000',
                'credit_total' => '0.000000',
                'balance_due' => $calculation->grandTotal,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $lineIdsBySourceLine = $this->createLines($invoice, $data, $calculation);
            $this->createSourceLines($invoice, $sourceLineRows, $lineIdsBySourceLine);

            $this->sources->createSources(
                $invoice,
                $data,
                $this->sourceAllocations->invoicedAmountBySource($sourceLineRows),
                $this->adjustments->allocatedAdjustmentAmountBySource($preparedAdjustments, $this->math),
            );
            $this->adjustments->createAdjustments($invoice, $preparedAdjustments);
            $this->balances->createBalance($invoice, $calculation->grandTotal);

            return $invoice->load([
                'lines',
                'sources',
                'sourceLines',
                'adjustments',
                'adjustmentAllocations',
                'balance',
            ]);
        });
    }

    public function preview(CreateInvoiceData $data): InvoiceCalculationResult
    {
        $this->validator->validateForCreation($data);
        $sourceLineRows = $this->sourceAllocations->prepareSourceLineAllocations($data);
        $preparedAdjustments = $this->adjustmentAllocations->prepareAdjustmentAllocations($data, $sourceLineRows);

        return $this->calculations->calculate($this->withAllocatedAdjustments($data, $preparedAdjustments));
    }

    /**
     * @param  list<array{adjustment: object, allocation: array<string, mixed>|null}>  $preparedAdjustments
     */
    private function withAllocatedAdjustments(CreateInvoiceData $data, array $preparedAdjustments): CreateInvoiceData
    {
        $adjustments = array_map(
            static fn (array $prepared): object => $prepared['adjustment'],
            $preparedAdjustments,
        );

        return new CreateInvoiceData(
            tenantId: $data->tenantId,
            invoiceType: $data->invoiceType,
            direction: $data->direction,
            invoiceDate: $data->invoiceDate,
            organizationUnitId: $data->organizationUnitId,
            invoiceNumber: $data->invoiceNumber,
            partyType: $data->partyType,
            partyId: $data->partyId,
            dueDate: $data->dueDate,
            currencyId: $data->currencyId,
            exchangeRate: $data->exchangeRate,
            status: $data->status,
            notes: $data->notes,
            createdBy: $data->createdBy,
            lines: $data->lines,
            sources: $data->sources,
            sourceLines: $data->sourceLines,
            adjustments: $adjustments,
        );
    }

    /**
     * @return array<string, int>
     */
    private function createLines(Invoice $invoice, CreateInvoiceData $data, InvoiceCalculationResult $calculation): array
    {
        $lineIdsBySourceLine = [];

        foreach ($data->lines as $index => $line) {
            /** @var InvoiceLineData $line */
            $model = InvoiceLine::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_id' => $invoice->getKey(),
                'line_number' => $line->lineNumber,
                'item_id' => $line->itemId,
                'description' => $line->description,
                'line_type' => $line->lineType->value,
                'quantity' => $this->math->normalize($line->quantity),
                'uom_id' => $line->uomId,
                'unit_price' => $this->math->normalize($line->unitPrice),
                'discount_amount' => $this->math->normalize($line->discountAmount),
                'tax_amount' => $this->math->normalize($line->taxAmount),
                'charge_amount' => $this->math->normalize($line->chargeAmount),
                'line_total' => $calculation->lineTotals[$index] ?? $this->calculations->lineTotal($line),
                'source_line_type' => $line->sourceLineType,
                'source_line_id' => $line->sourceLineId,
                'metadata' => $line->metadata,
            ]);

            if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
                $lineIdsBySourceLine[$this->sourceAllocations->sourceLineKey($line->sourceLineType, $line->sourceLineId)] = (int) $model->getKey();
            }
        }

        return $lineIdsBySourceLine;
    }

    /**
     * @param  list<array<string, mixed>>  $sourceLineRows
     * @param  array<string, int>  $lineIdsBySourceLine
     */
    private function createSourceLines(Invoice $invoice, array $sourceLineRows, array $lineIdsBySourceLine): void
    {
        foreach ($sourceLineRows as $row) {
            $invoiceLineKey = (string) $row['invoice_line_key'];
            unset($row['invoice_line_key']);

            InvoiceSourceLine::query()->create(array_merge($row, [
                'invoice_id' => $invoice->getKey(),
                'invoice_line_id' => $lineIdsBySourceLine[$invoiceLineKey] ?? null,
            ]));
        }
    }
}
