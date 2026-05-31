<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

interface PricingUsageSummaryServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summarizePriceList(int $priceListId, int $tenantId): array;

    /**
     * @return array<string, mixed>
     */
    public function summarizePricingRule(int $pricingRuleId, int $tenantId): array;
}
