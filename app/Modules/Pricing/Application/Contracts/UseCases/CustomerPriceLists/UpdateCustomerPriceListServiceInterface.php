<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists;

use Modules\Core\Application\Results\Result;

interface UpdateCustomerPriceListServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}