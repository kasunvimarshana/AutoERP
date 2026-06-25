<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use InvalidArgumentException;
use Modules\ReferenceData\Models\CurrencyModel;

final class TenantReferenceValidator
{
    public function __construct(private readonly CurrencyModel $currencies) {}

    public function assertActiveCurrency(?int $currencyId): void
    {
        if ($currencyId === null) {
            return;
        }

        if (! $this->currencies->newQuery()->whereKey($currencyId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('The selected currency is not active.');
        }
    }

    public function assertPlanPricing(string $price, ?int $currencyId): void
    {
        $this->assertActiveCurrency($currencyId);

        if ($price !== '0.000000' && $currencyId === null) {
            throw new InvalidArgumentException('A currency is required for a paid tenant plan.');
        }
    }
}
