<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists;

use Modules\Core\Application\Results\Result;

interface UpdateSupplierPriceListServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}