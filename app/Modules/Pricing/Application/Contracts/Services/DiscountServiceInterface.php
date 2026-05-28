<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface DiscountServiceInterface
{
    /** @param array<int, array<string, mixed>> $discounts */
    public function resolveDiscounts(array $discounts, float $baseAmount, float $quantity): Result;
}
