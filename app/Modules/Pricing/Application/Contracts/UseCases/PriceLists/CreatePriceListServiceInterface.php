<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\PriceLists;

use Modules\Core\Application\Results\Result;

interface CreatePriceListServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}