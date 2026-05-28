<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PricingRuleServiceInterface
{
    /** @param array<string, mixed> $payload */
    public function createPricingRule(array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function updatePricingRule(int|string $id, array $payload): Result;
}
