<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;

final class PurchaseAdjustmentAllocator
{
    public function __construct(private readonly DecimalMath $math) {}

    public function proportional(string $sourceAmount, string $eventBasis, string $totalBasis, string $remainingAmount, bool $isFinalEvent): string
    {
        $sourceAmount = $this->math->normalize($sourceAmount);
        $eventBasis = $this->math->normalize($eventBasis);
        $totalBasis = $this->math->normalize($totalBasis);
        $remainingAmount = $this->math->normalize($remainingAmount);

        if ($this->math->isZero($sourceAmount) || $this->math->isZero($eventBasis)) {
            return '0.000000';
        }
        if ($this->math->isZero($totalBasis)) {
            throw new InvalidArgumentException('Allocation basis must be greater than zero.');
        }
        if ($isFinalEvent) {
            return $remainingAmount;
        }

        $share = $this->math->mul($sourceAmount, $this->math->div($eventBasis, $totalBasis, 12));

        return $this->math->compare($share, $remainingAmount) > 0 ? $remainingAmount : $share;
    }

    /**
     * @param  array<int, string>  $linePlanAmounts
     * @param  array<int, array{event_quantity: string, source_quantity: string}>  $lineQuantities
     */
    public function manual(array $linePlanAmounts, array $lineQuantities, string $remainingAmount, bool $isFinalEvent): string
    {
        $remainingAmount = $this->math->normalize($remainingAmount);
        if ($isFinalEvent) {
            return $remainingAmount;
        }

        $amount = '0.000000';
        foreach ($lineQuantities as $lineId => $quantities) {
            $planAmount = $linePlanAmounts[$lineId] ?? '0.000000';
            if ($this->math->isZero($planAmount)) {
                continue;
            }
            $sourceQuantity = $this->math->normalize($quantities['source_quantity']);
            if ($this->math->isZero($sourceQuantity)) {
                continue;
            }
            $ratio = $this->math->div($quantities['event_quantity'], $sourceQuantity, 12);
            $amount = $this->math->add($amount, $this->math->mul($planAmount, $ratio));
        }

        return $this->math->compare($amount, $remainingAmount) > 0 ? $remainingAmount : $amount;
    }
}
