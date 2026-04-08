<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Pricing\Domain\Entities\PriceList;

interface PriceListServiceInterface
{
    public function getPriceList(string $tenantId, string $id): PriceList;

    /** @return PriceList[] */
    public function getAllPriceLists(string $tenantId): array;

    public function createPriceList(string $tenantId, array $data): PriceList;

    public function updatePriceList(string $tenantId, string $id, array $data): PriceList;

    public function deletePriceList(string $tenantId, string $id): void;
}
