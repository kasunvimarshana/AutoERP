<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * VolumePricingEngine
 *
 * Volume-based pricing with total quantity thresholds
 * Applies discount/price based on total purchase volume
 */
class VolumePricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $thresholds = $context['thresholds'] ?? [];

        if (empty($thresholds)) {
            return MathHelper::multiply($basePrice, $quantity);
        }

        usort($thresholds, fn ($a, $b) => MathHelper::compare($a['min_quantity'], $b['min_quantity']));

        $applicableThreshold = null;
        foreach ($thresholds as $threshold) {
            if (MathHelper::compare($quantity, $threshold['min_quantity']) >= 0) {
                $applicableThreshold = $threshold;
            } else {
                break;
            }
        }

        if (! $applicableThreshold) {
            return MathHelper::multiply($basePrice, $quantity);
        }

        if (isset($applicableThreshold['price'])) {
            return MathHelper::multiply($applicableThreshold['price'], $quantity);
        }

        if (isset($applicableThreshold['discount_percentage'])) {
            $discountAmount = MathHelper::percentage($basePrice, $applicableThreshold['discount_percentage']);
            $discountedPrice = MathHelper::subtract($basePrice, $discountAmount);

            return MathHelper::multiply($discountedPrice, $quantity);
        }

        return MathHelper::multiply($basePrice, $quantity);
    }

    public function getStrategy(): string
    {
        return 'volume';
    }

    public function validate(array $config): bool
    {
        if (! isset($config['thresholds']) || ! is_array($config['thresholds'])) {
            return false;
        }

        foreach ($config['thresholds'] as $threshold) {
            if (! isset($threshold['min_quantity'])) {
                return false;
            }

            if (! isset($threshold['price']) && ! isset($threshold['discount_percentage'])) {
                return false;
            }
        }

        return true;
    }
}
