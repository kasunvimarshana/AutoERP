<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\Services\DiscountServiceInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class DiscountService implements DiscountServiceInterface
{
    public function resolveDiscounts(array $discounts, float $baseAmount, float $quantity): Result
    {
        try {
            $appliedDiscounts = [];
            $discountAmount = 0.0;

            usort(
                $discounts,
                static function (array $left, array $right): int {
                    $rightPriority = (int) ($right['priority'] ?? 0);
                    $leftPriority = (int) ($left['priority'] ?? 0);

                    return $rightPriority <=> $leftPriority;
                }
            );

            foreach ($discounts as $discount) {
                if (! is_array($discount)) {
                    continue;
                }

                $minQuantity = (float) ($discount['min_quantity'] ?? 0);
                $maxQuantity = array_key_exists('max_quantity', $discount)
                    && $discount['max_quantity'] !== null
                    ? (float) $discount['max_quantity']
                    : null;

                if ($quantity < $minQuantity || ($maxQuantity !== null && $quantity > $maxQuantity)) {
                    continue;
                }

                $value = (float) ($discount['discount_value'] ?? 0);
                $type = strtolower((string) ($discount['discount_type'] ?? 'percentage'));
                $currentAmount = $type === 'fixed' ? $value : round($baseAmount * ($value / 100), 4);
                $discountAmount += $currentAmount;
                $appliedDiscounts[] = $discount;

                if (! (bool) ($discount['is_stackable'] ?? true) || (bool) ($discount['is_exclusive'] ?? false)) {
                    break;
                }
            }

            return Result::success([
                'applied_discounts' => $appliedDiscounts,
                'discount_amount' => round($discountAmount, 4),
                'discount_percentage' => $baseAmount > 0 ? round(($discountAmount / $baseAmount) * 100, 4) : 0.0,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
