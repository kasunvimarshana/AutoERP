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
    private const ZERO = '0.000000';

    // Multiplying two persisted decimals requires the sum of their scales.
    private const PRODUCT_SCALE = DecimalMath::SCALE + DecimalMath::SCALE;

    public function __construct(private readonly DecimalMath $math) {}

    /**
     * Source-owning modules must lock their source aggregate before calling the
     * creation command. Invoice owns only its persisted allocation history.
     *
     * @return list<array<string, mixed>>
     */
    public function prepareSourceLineAllocations(CreateInvoiceData $data, bool $lockRows = false): array
    {
        $rows = [];

        foreach ($data->sourceLines as $sourceLine) {
            $previous = $this->previousAllocations($data, $sourceLine, $lockRows);
            $previouslyInvoiced = $previous['quantity'];
            $remainingBeforeCurrent = $this->math->sub($sourceLine->sourceQuantity, $previouslyInvoiced);

            if ($this->math->compare($sourceLine->invoicedQuantity, $remainingBeforeCurrent) > 0) {
                throw new InvalidArgumentException('Invoice quantity cannot exceed source remaining quantity.');
            }

            $remainingQuantity = $this->math->sub($remainingBeforeCurrent, $sourceLine->invoicedQuantity);
            $invoicedLineTotal = $sourceLine->invoicedLineTotal
                ?? $this->proportionalAmount(
                    $sourceLine->sourceLineTotal,
                    $this->math->add($previouslyInvoiced, $sourceLine->invoicedQuantity),
                    $sourceLine->sourceQuantity,
                    $previous['amount'],
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
            $totals[$key] = $this->math->add($totals[$key] ?? self::ZERO, (string) $row['invoiced_line_total']);
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

    /** @return array{quantity: string, amount: string} */
    private function previousAllocations(
        CreateInvoiceData $data,
        InvoiceSourceLineData $sourceLine,
        bool $lockRows,
    ): array {
        $rows = InvoiceSourceLine::query()
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
                InvoiceStatus::Reversed->value,
            ]))
            ->when($lockRows, fn ($query) => $query->lockForUpdate())
            ->get(['invoiced_quantity', 'invoiced_line_total']);

        return [
            'quantity' => $this->math->sum($rows->map(static fn (InvoiceSourceLine $row): string => (string) $row->invoiced_quantity)),
            'amount' => $this->math->sum($rows->map(static fn (InvoiceSourceLine $row): string => (string) $row->invoiced_line_total)),
        ];
    }

    private function proportionalAmount(string $sourceAmount, string $cumulativeQuantity, string $sourceQuantity, string $previousAmount): string
    {
        if ($this->math->compare($sourceQuantity, self::ZERO) <= 0) {
            throw new InvalidArgumentException('Source quantity must be greater than zero when invoicing quantity.');
        }

        // Quantize once, after multiplication and division. Subtract surviving
        // monetary allocations so final quantities reconcile even after reversal.
        $cumulativeAmount = $this->math->div(
            $this->math->mul($sourceAmount, $cumulativeQuantity, self::PRODUCT_SCALE),
            $sourceQuantity,
        );
        $amount = $this->math->sub($cumulativeAmount, $previousAmount);
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException('Provide an explicit invoiced line total after reviewing earlier nonproportional allocations.');
        }

        return $amount;
    }
}
