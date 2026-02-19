<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Modules\Core\Helpers\MathHelper;

/**
 * Billing Calculation Service
 *
 * Handles all billing-related calculations including amounts, taxes, and discounts.
 */
class BillingCalculationService
{
    /**
     * Calculate subscription amount with discount and tax.
     */
    public function calculateSubscriptionAmount(
        string $basePrice,
        string $discountAmount = '0.00',
        float $taxRate = 0
    ): array {
        $amount = MathHelper::subtract($basePrice, $discountAmount);
        $taxAmount = MathHelper::multiply($amount, (string) $taxRate);
        $totalAmount = MathHelper::add($amount, $taxAmount);

        return [
            'amount' => $amount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Calculate usage-based charges.
     */
    public function calculateUsageCharge(
        string $quantity,
        string $unitPrice,
        string $tierMultiplier = '1.00'
    ): string {
        $baseAmount = MathHelper::multiply($quantity, $unitPrice);

        return MathHelper::multiply($baseAmount, $tierMultiplier);
    }

    /**
     * Calculate prorated amount for partial period.
     */
    public function calculateProratedAmount(
        string $fullAmount,
        int $totalDays,
        int $usedDays
    ): string {
        $dailyRate = MathHelper::divide($fullAmount, (string) $totalDays);

        return MathHelper::multiply($dailyRate, (string) $usedDays);
    }

    /**
     * Calculate refund amount.
     */
    public function calculateRefundAmount(
        string $paidAmount,
        int $totalDays,
        int $remainingDays,
        bool $prorated = true
    ): string {
        if (! $prorated) {
            return $paidAmount;
        }

        return $this->calculateProratedAmount($paidAmount, $totalDays, $remainingDays);
    }
}
