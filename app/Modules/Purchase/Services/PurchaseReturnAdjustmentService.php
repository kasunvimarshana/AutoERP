<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnAdjustmentAllocation;

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

    private function calculateFromReceiptLine(
        PurchaseReturn $return,
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        bool $mutate,
    ): string {
        $grnQuery = $sourceLine->goodsReceiptNote();
        $grnQuery->with(['adjustments' => function ($query) use ($mutate): void {
            if ($mutate) {
                $query->lockForUpdate();
            }
        }]);

        $grn = $grnQuery->first();
        if ($grn === null || $this->math->isZero((string) $grn->subtotal)) {
            return '0.000000';
        }

        $lineRatio = $this->math->div($this->math->mul($returnedQuantity, (string) $sourceLine->unit_price), (string) $grn->subtotal, 12);
        $netReturn = '0.000000';

        foreach ($grn->adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            $returnedAmount = $this->math->mul((string) $adjustment->amount, $lineRatio);
            $previouslyReturned = (string) $adjustment->returned_amount;
            $remaining = $this->math->sub((string) $adjustment->amount, $this->math->add($previouslyReturned, $returnedAmount));

            if ($this->math->isNegative($remaining)) {
                throw new \InvalidArgumentException('Purchase return adjustment allocation cannot exceed source adjustment amount.');
            }

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
}
