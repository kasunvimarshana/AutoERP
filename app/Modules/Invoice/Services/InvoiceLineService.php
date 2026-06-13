<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Invoice\Models\InvoiceSourceLine;

final class InvoiceLineService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCalculationService $calculations,
        private readonly InvoiceSourceAllocationService $sourceAllocations,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $sourceLineRows
     */
    public function create(
        Invoice $invoice,
        CreateInvoiceData $data,
        InvoiceCalculationResult $calculation,
        array $sourceLineRows,
    ): void {
        $lineIdsBySourceLine = $this->createInvoiceLines($invoice, $data, $calculation);

        foreach ($sourceLineRows as $row) {
            $invoiceLineKey = (string) $row['invoice_line_key'];
            unset($row['invoice_line_key']);

            InvoiceSourceLine::query()->create(array_merge($row, [
                'invoice_id' => $invoice->getKey(),
                'invoice_line_id' => $lineIdsBySourceLine[$invoiceLineKey] ?? null,
            ]));
        }
    }

    /**
     * @return array<string, int>
     */
    private function createInvoiceLines(
        Invoice $invoice,
        CreateInvoiceData $data,
        InvoiceCalculationResult $calculation,
    ): array {
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
                'line_total' => $calculation->lineTotals[$index]
                    ?? $this->calculations->lineTotal($line),
                'source_line_type' => $line->sourceLineType,
                'source_line_id' => $line->sourceLineId,
                'metadata' => $line->metadata,
            ]);

            if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
                $key = $this->sourceAllocations->sourceLineKey(
                    $line->sourceLineType,
                    $line->sourceLineId,
                );
                $lineIdsBySourceLine[$key] = (int) $model->getKey();
            }
        }

        return $lineIdsBySourceLine;
    }
}
