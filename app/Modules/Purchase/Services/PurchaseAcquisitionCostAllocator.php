<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseAcquisitionCostAllocator
{
    public function __construct(private readonly DecimalMath $math) {}

    public function unitCostForReceiptLine(GoodsReceiptNote $grn, GoodsReceiptNoteLine $line): string
    {
        $amounts = $this->lineCapitalizedAmounts($grn);
        $amount = $amounts[(int) $line->getKey()] ?? $this->lineNetCost($line);
        $quantity = (string) $line->accepted_quantity;
        if ($this->math->isZero($quantity)) {
            return '0.000000';
        }

        return $this->math->div($amount, $quantity);
    }

    public function goodsReceiptStockValue(GoodsReceiptNote $grn): string
    {
        return $this->math->sum(array_values($this->lineCapitalizedAmounts($grn)));
    }

    /**
     * @return array<int, string>
     */
    public function lineCapitalizedAmounts(GoodsReceiptNote $grn): array
    {
        $grn->loadMissing(['lines', 'adjustments']);
        $lines = $grn->lines
            ->filter(fn (GoodsReceiptNoteLine $line): bool => ! $this->math->isZero((string) $line->accepted_quantity))
            ->sortBy(fn (GoodsReceiptNoteLine $line): int => (int) $line->getKey())
            ->values();

        $amounts = [];
        $basisTotal = '0.000000';
        foreach ($lines as $line) {
            $net = $this->lineNetCost($line);
            $amounts[(int) $line->getKey()] = $net;
            $basisTotal = $this->math->add($basisTotal, $net);
        }

        if ($lines->isEmpty() || $this->math->isZero($basisTotal)) {
            return $amounts;
        }

        foreach ($this->capitalizableAdjustments($grn) as $adjustment) {
            $signed = $adjustment->effect === PurchaseAdjustmentEffect::Decrease
                ? '-'.$this->math->normalize((string) $adjustment->amount)
                : $this->math->normalize((string) $adjustment->amount);
            $allocated = '0.000000';
            $lastIndex = $lines->count() - 1;

            foreach ($lines as $index => $line) {
                $lineId = (int) $line->getKey();
                if ($index === $lastIndex) {
                    $share = $this->math->sub($signed, $allocated);
                } else {
                    $ratio = $this->math->div($amounts[$lineId], $basisTotal, 12);
                    $share = $this->math->mul($signed, $ratio);
                    $allocated = $this->math->add($allocated, $share);
                }
                $amounts[$lineId] = $this->math->add($amounts[$lineId], $share);
            }
        }

        return $amounts;
    }

    private function lineNetCost(GoodsReceiptNoteLine $line): string
    {
        return $this->math->add(
            $this->math->sub((string) $line->line_subtotal, (string) $line->discount_amount),
            (string) $line->charge_amount,
        );
    }

    /**
     * @return list<PurchaseHeaderAdjustment>
     */
    private function capitalizableAdjustments(GoodsReceiptNote $grn): array
    {
        $allowedTypes = [
            PurchaseAdjustmentType::Discount->value => true,
            PurchaseAdjustmentType::Freight->value => true,
            PurchaseAdjustmentType::Charge->value => true,
            PurchaseAdjustmentType::Insurance->value => true,
            PurchaseAdjustmentType::Duty->value => true,
            PurchaseAdjustmentType::Levy->value => true,
        ];

        return $grn->adjustments
            ->filter(function (PurchaseHeaderAdjustment $adjustment) use ($allowedTypes): bool {
                if ($this->math->isZero((string) $adjustment->amount)) {
                    return false;
                }

                $type = $adjustment->adjustment_type instanceof \BackedEnum
                    ? $adjustment->adjustment_type->value
                    : (string) $adjustment->adjustment_type;
                $treatment = (string) ($adjustment->cost_treatment ?? '');

                return isset($allowedTypes[$type])
                    || str_contains($treatment, 'landed_cost')
                    || $treatment === 'inventory_cost_reduction';
            })
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->all();
    }
}
