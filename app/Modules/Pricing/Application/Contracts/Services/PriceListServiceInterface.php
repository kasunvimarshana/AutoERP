<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PriceListServiceInterface
{
    /** @param array<string, mixed> $payload */
    public function createPriceList(array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function updatePriceList(int|string $id, array $payload): Result;
}
