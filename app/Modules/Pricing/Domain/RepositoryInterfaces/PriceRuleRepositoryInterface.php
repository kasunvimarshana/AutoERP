<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Pricing\Domain\Entities\PriceRule;

interface PriceRuleRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PriceRule;

    /** @return PriceRule[] */
    public function findAll(string $tenantId): array;

    /** @return PriceRule[] */
    public function findByPriceList(string $tenantId, string $priceListId): array;

    public function save(PriceRule $priceRule): void;

    public function delete(string $tenantId, string $id): void;
}
