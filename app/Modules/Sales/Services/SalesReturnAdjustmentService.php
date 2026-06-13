<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesHeaderAdjustment;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnAdjustmentAllocation;

final class SalesReturnAdjustmentService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function allocate(SalesReturn $return): string
    {
        $net = '0.000000';
        foreach ($this->returnedTotalsBySource($return) as $sourceKey => $returnedLineTotal) {
            [$sourceType, $sourceId] = explode(':', $sourceKey, 2);
            $sourceSubtotal = $this->sourceSubtotal($sourceType, (int) $sourceId);
            if ($this->math->isZero($sourceSubtotal)) {
                continue;
            }

            $ratio = $this->math->div($returnedLineTotal, $sourceSubtotal, 12);
            $adjustments = SalesHeaderAdjustment::query()
                ->where('source_type', $sourceType)
                ->where('source_id', (int) $sourceId)
                ->lockForUpdate()
                ->get();

            foreach ($adjustments as $adjustment) {
                $returnedAmount = $this->math->mul((string) $adjustment->amount, $ratio);
                $previouslyReturned = (string) $adjustment->returned_amount;
                $remaining = $this->math->sub(
                    (string) $adjustment->amount,
                    $this->math->add($previouslyReturned, $returnedAmount),
                );
                $this->createAllocation(
                    $return,
                    $adjustment,
                    $previouslyReturned,
                    $returnedAmount,
                    $remaining,
                );
                $adjustment->returned_amount = $this->math->add(
                    $previouslyReturned,
                    $returnedAmount,
                );
                $adjustment->remaining_amount = $remaining;
                $adjustment->save();

                $net = $adjustment->effect->value === 'increase'
                    ? $this->math->add($net, $returnedAmount)
                    : $this->math->sub($net, $returnedAmount);
            }
        }

        return $net;
    }

    public function release(SalesReturn $return): void
    {
        $return->loadMissing('adjustmentAllocations');
        foreach ($return->adjustmentAllocations as $allocation) {
            $adjustment = SalesHeaderAdjustment::query()
                ->lockForUpdate()
                ->findOrFail($allocation->sales_header_adjustment_id);
            $returnedAmount = $this->math->sub(
                (string) $adjustment->returned_amount,
                (string) $allocation->returned_amount,
            );
            $adjustment->returned_amount = $this->math->compare(
                $returnedAmount,
                '0.000000',
            ) < 0 ? '0.000000' : $returnedAmount;
            $adjustment->remaining_amount = $this->math->sub(
                (string) $adjustment->amount,
                (string) $adjustment->returned_amount,
            );
            $adjustment->save();
        }
    }

    /**
     * @return array<string, string>
     */
    private function returnedTotalsBySource(SalesReturn $return): array
    {
        $return->load('lines');
        $totals = [];
        foreach ($return->lines as $line) {
            $source = $this->sourceDocument($line->source_line_type, $line->source_line_id);
            if ($source === null) {
                continue;
            }
            $key = $source[0].':'.$source[1];
            $totals[$key] = $this->math->add(
                $totals[$key] ?? '0.000000',
                (string) $line->line_total,
            );
        }

        return $totals;
    }

    /**
     * @return array{string, int}|null
     */
    private function sourceDocument(?string $lineType, ?int $lineId): ?array
    {
        if ($lineType === 'sales_delivery_line') {
            $line = SalesDeliveryLine::query()->find($lineId);

            return $line === null
                ? null
                : ['sales_delivery', (int) $line->sales_delivery_id];
        }
        if ($lineType === 'sales_order_line') {
            $line = SalesOrderLine::query()->find($lineId);

            return $line === null
                ? null
                : ['sales_order', (int) $line->sales_order_id];
        }

        return null;
    }

    private function sourceSubtotal(string $sourceType, int $sourceId): string
    {
        if ($sourceType === 'sales_order') {
            return (string) SalesOrder::query()->findOrFail($sourceId)->subtotal;
        }

        $delivery = SalesDelivery::query()->with('lines')->findOrFail($sourceId);
        $total = '0.000000';
        foreach ($delivery->lines as $line) {
            $total = $this->math->add(
                $total,
                $this->math->mul(
                    (string) $line->delivered_quantity,
                    (string) $line->unit_price,
                ),
            );
        }

        return $total;
    }

    private function createAllocation(
        SalesReturn $return,
        SalesHeaderAdjustment $adjustment,
        string $previouslyReturned,
        string $returnedAmount,
        string $remaining,
    ): void {
        SalesReturnAdjustmentAllocation::query()->create([
            'tenant_id' => $return->tenant_id,
            'organization_unit_id' => $return->organization_unit_id,
            'sales_return_id' => $return->getKey(),
            'sales_header_adjustment_id' => $adjustment->getKey(),
            'adjustment_type' => $adjustment->adjustment_type,
            'effect' => $adjustment->effect,
            'source_amount' => $adjustment->amount,
            'previously_returned_amount' => $previouslyReturned,
            'returned_amount' => $returnedAmount,
            'remaining_amount' => $remaining,
        ]);
    }
}
