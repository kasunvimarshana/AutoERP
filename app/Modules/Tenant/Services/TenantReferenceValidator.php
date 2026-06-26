<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use InvalidArgumentException;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;

final class TenantReferenceValidator
{
    public function __construct(private readonly CurrencyDirectoryInterface $currencies) {}

    public function assertActiveCurrency(?int $currencyId): void
    {
        if ($currencyId === null) {
            return;
        }

        if (! $this->currencies->isActive($currencyId)) {
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
