<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PriceResolverServiceInterface
{
    /** @param array<string, mixed> $context */
    public function resolvePrice(array $context): Result;
}
