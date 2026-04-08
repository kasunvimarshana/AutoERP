<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Pricing\Domain\Entities\PriceList;

interface PriceListRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PriceList;

    /** @return PriceList[] */
    public function findAll(string $tenantId): array;

    public function findDefault(string $tenantId): ?PriceList;

    public function save(PriceList $priceList): void;

    public function delete(string $tenantId, string $id): void;
}
