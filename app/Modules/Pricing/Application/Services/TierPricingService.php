<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\Services\TierPricingServiceInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class TierPricingService implements TierPricingServiceInterface
{
    public function resolveTier(array $tiers, float $quantity): Result
    {
        try {
            $bestTier = null;

            foreach ($tiers as $tier) {
                if (! is_array($tier)) {
                    continue;
                }

                $min = (float) ($tier['min_quantity'] ?? 0);
                $max = array_key_exists('max_quantity', $tier)
                    && $tier['max_quantity'] !== null
                    ? (float) $tier['max_quantity']
                    : null;

                if ($quantity < $min || ($max !== null && $quantity > $max)) {
                    continue;
                }

                if ($bestTier === null || (int) ($tier['priority'] ?? 0) > (int) ($bestTier['priority'] ?? 0)) {
                    $bestTier = $tier;
                }
            }

            if ($bestTier === null) {
                return Result::success([
                    'applied_tier' => null,
                    'tier_price' => null,
                    'tier_adjustment' => 0.0,
                ]);
            }

            $tierPrice = array_key_exists('price', $bestTier)
                ? ($bestTier['price'] !== null ? (float) $bestTier['price'] : null)
                : null;
            $adjustmentValue = (float) ($bestTier['adjustment_value'] ?? 0);
            $adjustmentType = (string) ($bestTier['adjustment_type'] ?? 'override');

            return Result::success([
                'applied_tier' => $bestTier,
                'tier_price' => $tierPrice,
                'tier_adjustment' => $adjustmentType === 'percentage' ? $adjustmentValue : $adjustmentValue,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
