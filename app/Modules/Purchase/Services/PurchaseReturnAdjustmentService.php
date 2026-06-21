<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnAdjustmentAllocation;
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseReturnAdjustmentService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function allocateFromReceiptLine(PurchaseReturn $return, GoodsReceiptNoteLine $sourceLine, string $returnedQuantity): string
    {
        return $this->calculateFromReceiptLine($return, $sourceLine, $returnedQuantity, mutate: true);
    }

    public function previewFromReceiptLine(PurchaseReturn $return, GoodsReceiptNoteLine $sourceLine, string $returnedQuantity): string
    {
        return $this->calculateFromReceiptLine($return, $sourceLine, $returnedQuantity, mutate: false);
    }

    /**
     * @param  Collection<int, GoodsReceiptNoteLine>  $receiptLines
     * @param  Collection<int, PurchaseHeaderAdjustment>  $adjustments
     * @param  Collection<int, PurchaseReturnAdjustmentAllocation>  $allocations
     */
    public function allocateFromLockedReceiptLine(
        PurchaseReturn $return,
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        Collection $receiptLines,
        Collection $adjustments,
        Collection $allocations,
    ): string {
        $grn = $sourceLine->goodsReceiptNote;
        if ($grn === null || $this->math->isZero((string) $grn->subtotal)) {
            return '0.000000';
        }

        $lineRatio = $this->math->div($this->math->mul($returnedQuantity, (string) $sourceLine->unit_price), (string) $grn->subtotal, 12);
        $isFinalReceiptReturn = $this->isFinalReceiptReturnFromLockedLines($return, $sourceLine, $receiptLines);
        $netReturn = '0.000000';

        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            $previouslyReturned = (string) $adjustment->returned_amount;
            $returnedAmount = $isFinalReceiptReturn
                ? $this->math->sub((string) $adjustment->amount, $previouslyReturned)
                : $this->math->mul((string) $adjustment->amount, $lineRatio);
            $remaining = $this->math->sub((string) $adjustment->amount, $this->math->add($previouslyReturned, $returnedAmount));

            if ($this->math->isNegative($remaining)) {
                throw new \InvalidArgumentException('Purchase return adjustment allocation cannot exceed source adjustment amount.');
            }
            $this->assertAllocationAmounts($adjustment, $previouslyReturned, $returnedAmount, $remaining);

            $allocation = $allocations->first(
                fn (PurchaseReturnAdjustmentAllocation $candidate): bool => (int) $candidate->purchase_return_id === (int) $return->getKey()
                    && (int) $candidate->purchase_header_adjustment_id === (int) $adjustment->getKey(),
            );

            if ($allocation instanceof PurchaseReturnAdjustmentAllocation) {
                $allocation->returned_amount = $this->math->add((string) $allocation->returned_amount, $returnedAmount);
                $allocation->remaining_amount = $remaining;
                $allocation->save();
            } else {
                $allocation = PurchaseReturnAdjustmentAllocation::query()->create([
                    'tenant_id' => $return->tenant_id,
                    'organization_unit_id' => $return->organization_unit_id,
                    'purchase_return_id' => $return->getKey(),
                    'purchase_header_adjustment_id' => $adjustment->getKey(),
                    'adjustment_type' => $adjustment->adjustment_type,
                    'effect' => $adjustment->effect,
                    'source_amount' => $adjustment->amount,
                    'previously_returned_amount' => $previouslyReturned,
                    'returned_amount' => $returnedAmount,
                    'remaining_amount' => $remaining,
                ]);
                $allocations->push($allocation);
            }

            $adjustment->returned_amount = $this->math->add($previouslyReturned, $returnedAmount);
            $adjustment->remaining_amount = $remaining;
            $adjustment->save();

            $netReturn = $adjustment->effect->value === 'increase'
                ? $this->math->add($netReturn, $returnedAmount)
                : $this->math->sub($netReturn, $returnedAmount);
        }

        return $netReturn;
    }

    private function calculateFromReceiptLine(
        PurchaseReturn $return,
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        bool $mutate,
    ): string {
        $grnQuery = $sourceLine->goodsReceiptNote();
        $grnQuery->with([
            'lines',
            'adjustments' => function ($query) use ($mutate): void {
                if ($mutate) {
                    $query->lockForUpdate();
                }
            },
        ]);

        $grn = $grnQuery->first();
        if ($grn === null || $this->math->isZero((string) $grn->subtotal)) {
            return '0.000000';
        }

        if ($mutate) {
            $adjustmentIds = $grn->adjustments
                ->filter(fn ($adjustment): bool => $adjustment instanceof PurchaseHeaderAdjustment)
                ->map(fn (PurchaseHeaderAdjustment $adjustment): int => (int) $adjustment->getKey())
                ->values()
                ->all();

            if ($adjustmentIds !== []) {
                PurchaseReturnAdjustmentAllocation::query()
                    ->whereIn('purchase_header_adjustment_id', $adjustmentIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }
        }

        $lineRatio = $this->math->div($this->math->mul($returnedQuantity, (string) $sourceLine->unit_price), (string) $grn->subtotal, 12);
        $isFinalReceiptReturn = $this->isFinalReceiptReturn($return, $sourceLine, $returnedQuantity, $grn->lines);
        $netReturn = '0.000000';

        foreach ($grn->adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            $previouslyReturned = (string) $adjustment->returned_amount;
            $returnedAmount = $isFinalReceiptReturn
                ? $this->math->sub((string) $adjustment->amount, $previouslyReturned)
                : $this->math->mul((string) $adjustment->amount, $lineRatio);
            $remaining = $this->math->sub((string) $adjustment->amount, $this->math->add($previouslyReturned, $returnedAmount));

            if ($this->math->isNegative($remaining)) {
                throw new \InvalidArgumentException('Purchase return adjustment allocation cannot exceed source adjustment amount.');
            }
            $this->assertAllocationAmounts($adjustment, $previouslyReturned, $returnedAmount, $remaining);

            if ($mutate) {
                $allocation = PurchaseReturnAdjustmentAllocation::query()
                    ->where('purchase_return_id', $return->getKey())
                    ->where('purchase_header_adjustment_id', $adjustment->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($allocation instanceof PurchaseReturnAdjustmentAllocation) {
                    $allocation->returned_amount = $this->math->add((string) $allocation->returned_amount, $returnedAmount);
                    $allocation->remaining_amount = $remaining;
                    $allocation->save();
                } else {
                    PurchaseReturnAdjustmentAllocation::query()->create([
                        'tenant_id' => $return->tenant_id,
                        'organization_unit_id' => $return->organization_unit_id,
                        'purchase_return_id' => $return->getKey(),
                        'purchase_header_adjustment_id' => $adjustment->getKey(),
                        'adjustment_type' => $adjustment->adjustment_type,
                        'effect' => $adjustment->effect,
                        'source_amount' => $adjustment->amount,
                        'previously_returned_amount' => $previouslyReturned,
                        'returned_amount' => $returnedAmount,
                        'remaining_amount' => $remaining,
                    ]);
                }

                $adjustment->returned_amount = $this->math->add($previouslyReturned, $returnedAmount);
                $adjustment->remaining_amount = $remaining;
                $adjustment->save();
            }

            $netReturn = $adjustment->effect->value === 'increase'
                ? $this->math->add($netReturn, $returnedAmount)
                : $this->math->sub($netReturn, $returnedAmount);
        }

        return $netReturn;
    }

    private function assertAllocationAmounts(
        PurchaseHeaderAdjustment $adjustment,
        string $previouslyReturned,
        string $returnedAmount,
        string $remaining,
    ): void {
        foreach ([
            'source amount' => (string) $adjustment->amount,
            'previously returned amount' => $previouslyReturned,
            'returned amount' => $returnedAmount,
            'remaining amount' => $remaining,
        ] as $label => $amount) {
            if ($this->math->isNegative($amount)) {
                throw new \InvalidArgumentException("Purchase return adjustment {$label} cannot be negative.");
            }
        }
    }

    private function isFinalReceiptReturn(
        PurchaseReturn $return,
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        iterable $receiptLines,
    ): bool {
        $currentReturnQuantities = [];
        $hasCurrentLine = false;
        $returnLines = PurchaseReturnLine::query()
            ->where('purchase_return_id', $return->getKey())
            ->where('source_line_type', 'goods_receipt_note_line')
            ->get(['source_line_id', 'returned_quantity']);

        foreach ($returnLines as $line) {
            $sourceLineId = (int) $line->source_line_id;
            $currentReturnQuantities[$sourceLineId] = $this->math->add(
                $currentReturnQuantities[$sourceLineId] ?? '0.000000',
                (string) $line->returned_quantity,
            );
            if ($sourceLineId === (int) $sourceLine->getKey()) {
                $hasCurrentLine = true;
            }
        }

        if (! $hasCurrentLine) {
            $currentReturnQuantities[(int) $sourceLine->getKey()] = $this->math->add(
                $currentReturnQuantities[(int) $sourceLine->getKey()] ?? '0.000000',
                $returnedQuantity,
            );
        }

        foreach ($receiptLines as $receiptLine) {
            if (! $receiptLine instanceof GoodsReceiptNoteLine) {
                continue;
            }

            $projectedReturned = $this->math->add(
                (string) $receiptLine->returned_quantity,
                $currentReturnQuantities[(int) $receiptLine->getKey()] ?? '0.000000',
            );
            if ($this->math->compare($projectedReturned, (string) $receiptLine->accepted_quantity) < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, GoodsReceiptNoteLine>  $receiptLines
     */
    private function isFinalReceiptReturnFromLockedLines(
        PurchaseReturn $return,
        GoodsReceiptNoteLine $sourceLine,
        Collection $receiptLines,
    ): bool {
        $currentReturnQuantities = [];
        foreach ($return->lines as $line) {
            if (! $line instanceof PurchaseReturnLine || $line->source_line_type !== 'goods_receipt_note_line') {
                continue;
            }

            $sourceLineId = (int) $line->source_line_id;
            $currentReturnQuantities[$sourceLineId] = $this->math->add(
                $currentReturnQuantities[$sourceLineId] ?? '0.000000',
                (string) $line->returned_quantity,
            );
        }

        foreach ($receiptLines as $receiptLine) {
            if (! $receiptLine instanceof GoodsReceiptNoteLine) {
                continue;
            }

            $projectedReturned = $this->math->add(
                (string) $receiptLine->returned_quantity,
                $currentReturnQuantities[(int) $receiptLine->getKey()] ?? '0.000000',
            );
            if ($this->math->compare($projectedReturned, (string) $receiptLine->accepted_quantity) < 0) {
                return false;
            }
        }

        return $receiptLines->isNotEmpty();
    }
}
