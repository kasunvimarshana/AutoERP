<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Models\InvoiceAdjustmentAllocation;

final class InvoiceAdjustmentAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceSourceAllocationService $sources,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $sourceLineRows
     * @return list<array{adjustment: InvoiceAdjustmentData, allocation: array<string, mixed>|null}>
     */
    public function prepareAdjustmentAllocations(CreateInvoiceData $data, array $sourceLineRows): array
    {
        $prepared = [];
        $seenSourceAdjustments = [];
        $invoicedAmountBySource = $this->sources->invoicedAmountBySource($sourceLineRows);

        foreach ($data->adjustments as $adjustment) {
            $sourceAmount = $this->sourceAmount($adjustment, $data);
            $allocatedAmount = $this->allocatedAmount($adjustment, $sourceAmount, $data, $invoicedAmountBySource);
            $allocation = null;

            if ($adjustment->sourceAdjustmentType !== null && $adjustment->sourceAdjustmentId !== null) {
                if ($adjustment->sourceType === null || $adjustment->sourceId === null) {
                    throw new InvalidArgumentException('Source adjustment allocations require source type and id.');
                }

                $key = $adjustment->sourceAdjustmentType.':'.$adjustment->sourceAdjustmentId;
                if (isset($seenSourceAdjustments[$key])) {
                    throw new InvalidArgumentException('Duplicate source adjustment allocation in the same invoice.');
                }
                $seenSourceAdjustments[$key] = true;

                $previouslyAllocated = $this->previouslyAllocatedAmount($data, $adjustment);
                $remainingAmount = $this->math->sub($sourceAmount, $previouslyAllocated);
                if ($this->math->compare($allocatedAmount, $remainingAmount) > 0) {
                    throw new InvalidArgumentException('Adjustment allocation cannot exceed remaining adjustment amount.');
                }

                $allocation = [
                    'source_adjustment_type' => $adjustment->sourceAdjustmentType,
                    'source_adjustment_id' => $adjustment->sourceAdjustmentId,
                    'source_type' => $adjustment->sourceType,
                    'source_id' => $adjustment->sourceId,
                    'adjustment_type' => $adjustment->adjustmentType->value,
                    'effect' => $adjustment->effect->value,
                    'allocation_method' => $adjustment->allocationMethod->value,
                    'source_amount' => $sourceAmount,
                    'previously_allocated_amount' => $previouslyAllocated,
                    'allocated_amount' => $allocatedAmount,
                    'remaining_amount' => $this->math->sub($remainingAmount, $allocatedAmount),
                ];
            }

            $prepared[] = [
                'adjustment' => new InvoiceAdjustmentData(
                    name: $adjustment->name,
                    adjustmentType: $adjustment->adjustmentType,
                    effect: $adjustment->effect,
                    amount: $allocatedAmount,
                    sourceAdjustmentType: $adjustment->sourceAdjustmentType,
                    sourceAdjustmentId: $adjustment->sourceAdjustmentId,
                    sourceType: $adjustment->sourceType,
                    sourceId: $adjustment->sourceId,
                    calculationType: $adjustment->calculationType,
                    rate: $adjustment->rate,
                    sourceAmount: $sourceAmount,
                    allocationMethod: $adjustment->allocationMethod,
                    isSystemGenerated: $adjustment->isSystemGenerated,
                    description: $adjustment->description,
                ),
                'allocation' => $allocation,
            ];
        }

        return $prepared;
    }

    private function sourceAmount(InvoiceAdjustmentData $adjustment, CreateInvoiceData $data): string
    {
        if ($adjustment->calculationType === 'percentage') {
            $source = $this->findSource($data, $adjustment->sourceType, $adjustment->sourceId);
            if (! $source instanceof InvoiceSourceData) {
                throw new InvalidArgumentException('Percentage adjustments require a matching source.');
            }

            return $this->math->div(
                $this->math->mul($source->sourceSubtotal, $adjustment->rate, 12),
                '100',
            );
        }

        return $this->math->normalize($adjustment->sourceAmount ?? $adjustment->amount);
    }

    /**
     * @param  array<string, string>  $invoicedAmountBySource
     */
    private function allocatedAmount(
        InvoiceAdjustmentData $adjustment,
        string $sourceAmount,
        CreateInvoiceData $data,
        array $invoicedAmountBySource,
    ): string {
        if ($adjustment->allocationMethod === AllocationMethod::Manual) {
            return $this->math->normalize($adjustment->amount);
        }

        $previouslyAllocated = $adjustment->sourceAdjustmentType !== null && $adjustment->sourceAdjustmentId !== null
            ? $this->previouslyAllocatedAmount($data, $adjustment)
            : '0.000000';

        if ($adjustment->allocationMethod === AllocationMethod::FirstInvoice) {
            return $this->math->isZero($previouslyAllocated) ? $sourceAmount : '0.000000';
        }

        if ($adjustment->allocationMethod === AllocationMethod::LastInvoice) {
            return $this->math->sub($sourceAmount, $previouslyAllocated);
        }

        $source = $this->findSource($data, $adjustment->sourceType, $adjustment->sourceId);
        if (! $source instanceof InvoiceSourceData) {
            throw new InvalidArgumentException('Proportional adjustments require a matching source.');
        }

        $sourceKey = $this->sources->sourceKey($source->sourceType, $source->sourceId);
        $selectedAmount = $invoicedAmountBySource[$sourceKey] ?? '0.000000';
        if ($this->math->isZero($source->sourceSubtotal)) {
            throw new InvalidArgumentException('Source subtotal must be greater than zero for proportional allocation.');
        }

        return $this->math->mul($sourceAmount, $this->math->div($selectedAmount, $source->sourceSubtotal, 12));
    }

    private function previouslyAllocatedAmount(CreateInvoiceData $data, InvoiceAdjustmentData $adjustment): string
    {
        if ($adjustment->sourceAdjustmentType === null || $adjustment->sourceAdjustmentId === null) {
            return '0.000000';
        }

        return $this->math->normalize((string) InvoiceAdjustmentAllocation::query()
            ->where('tenant_id', $data->tenantId)
            ->where('source_adjustment_type', $adjustment->sourceAdjustmentType)
            ->where('source_adjustment_id', $adjustment->sourceAdjustmentId)
            ->sum('allocated_amount'));
    }

    private function findSource(CreateInvoiceData $data, ?string $sourceType, ?int $sourceId): ?InvoiceSourceData
    {
        if ($sourceType === null || $sourceId === null) {
            return null;
        }

        foreach ($data->sources as $source) {
            if ($source->sourceType === $sourceType && $source->sourceId === $sourceId) {
                return $source;
            }
        }

        return null;
    }
}
