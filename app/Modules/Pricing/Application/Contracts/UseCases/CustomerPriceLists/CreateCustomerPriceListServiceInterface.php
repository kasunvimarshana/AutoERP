<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists;

use Modules\Core\Application\Results\Result;

interface CreateCustomerPriceListServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}