<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface TierPricingServiceInterface
{
    /** @param array<int, array<string, mixed>> $tiers */
    public function resolveTier(array $tiers, float $quantity): Result;
}
