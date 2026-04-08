<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Pricing\Domain\Entities\PriceRule;

interface PriceRuleServiceInterface
{
    public function getPriceRule(string $tenantId, string $id): PriceRule;

    /** @return PriceRule[] */
    public function getRulesForPriceList(string $tenantId, string $priceListId): array;

    public function createPriceRule(string $tenantId, array $data): PriceRule;

    public function updatePriceRule(string $tenantId, string $id, array $data): PriceRule;

    public function deletePriceRule(string $tenantId, string $id): void;

    public function resolvePrice(
        string $tenantId,
        string $priceListId,
        ?string $productId,
        ?string $variantId,
        ?string $categoryId,
        float $qty
    ): float;
}
