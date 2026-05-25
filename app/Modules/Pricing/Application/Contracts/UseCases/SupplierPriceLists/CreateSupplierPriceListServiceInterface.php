<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists;

use Modules\Core\Application\Results\Result;

interface CreateSupplierPriceListServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}