<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceSourceLine;

final class InvoiceSourceAllocationService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function prepareSourceLineAllocations(CreateInvoiceData $data): array
    {
        $rows = [];

        foreach ($data->sourceLines as $sourceLine) {
            $previouslyInvoiced = $this->previouslyInvoicedQuantity($data, $sourceLine);
            $remainingBeforeCurrent = $this->math->sub($sourceLine->sourceQuantity, $previouslyInvoiced);

            if ($this->math->compare($sourceLine->invoicedQuantity, $remainingBeforeCurrent) > 0) {
                throw new InvalidArgumentException('Invoice quantity cannot exceed source remaining quantity.');
            }

            $remainingQuantity = $this->math->sub($remainingBeforeCurrent, $sourceLine->invoicedQuantity);
            $invoicedLineTotal = $sourceLine->invoicedLineTotal
                ?? $this->proportionalAmount(
                    $sourceLine->sourceLineTotal,
                    $sourceLine->invoicedQuantity,
                    $sourceLine->sourceQuantity,
                );

            $rows[] = [
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'source_type' => $sourceLine->sourceType,
                'source_id' => $sourceLine->sourceId,
                'source_line_type' => $sourceLine->sourceLineType,
                'source_line_id' => $sourceLine->sourceLineId,
                'source_quantity' => $this->math->normalize($sourceLine->sourceQuantity),
                'previously_invoiced_quantity' => $previouslyInvoiced,
                'invoiced_quantity' => $this->math->normalize($sourceLine->invoicedQuantity),
                'remaining_quantity' => $remainingQuantity,
                'source_unit_price' => $this->math->normalize($sourceLine->sourceUnitPrice),
                'source_line_total' => $this->math->normalize($sourceLine->sourceLineTotal),
                'invoiced_line_total' => $invoicedLineTotal,
                'invoice_line_key' => $this->sourceLineKey($sourceLine->sourceLineType, $sourceLine->sourceLineId),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $sourceLineRows
     * @return array<string, string>
     */
    public function invoicedAmountBySource(array $sourceLineRows): array
    {
        $totals = [];
        foreach ($sourceLineRows as $row) {
            $key = $this->sourceKey((string) $row['source_type'], (int) $row['source_id']);
            $totals[$key] = $this->math->add($totals[$key] ?? '0.000000', (string) $row['invoiced_line_total']);
        }

        return $totals;
    }

    public function sourceKey(string $sourceType, int $sourceId): string
    {
        return $sourceType.':'.$sourceId;
    }

    public function sourceLineKey(string $sourceLineType, int $sourceLineId): string
    {
        return $sourceLineType.':'.$sourceLineId;
    }

    private function previouslyInvoicedQuantity(CreateInvoiceData $data, InvoiceSourceLineData $sourceLine): string
    {
        $databaseQuantity = (string) InvoiceSourceLine::query()
            ->where('tenant_id', $data->tenantId)
            ->when(
                $data->organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $data->organizationUnitId),
            )
            ->where('source_type', $sourceLine->sourceType)
            ->where('source_id', $sourceLine->sourceId)
            ->where('source_line_type', $sourceLine->sourceLineType)
            ->where('source_line_id', $sourceLine->sourceLineId)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ]))
            ->sum('invoiced_quantity');

        return $this->math->compare($databaseQuantity, $sourceLine->previouslyInvoicedQuantity) > 0
            ? $this->math->normalize($databaseQuantity)
            : $this->math->normalize($sourceLine->previouslyInvoicedQuantity);
    }

    private function proportionalAmount(string $sourceAmount, string $selectedQuantity, string $sourceQuantity): string
    {
        if ($this->math->isZero($sourceQuantity)) {
            if ($this->math->isZero($selectedQuantity)) {
                return '0.000000';
            }

            throw new InvalidArgumentException('Source quantity must be greater than zero when invoicing quantity.');
        }

        $ratio = $this->math->div($selectedQuantity, $sourceQuantity, 12);

        return $this->math->mul($sourceAmount, $ratio);
    }
}
