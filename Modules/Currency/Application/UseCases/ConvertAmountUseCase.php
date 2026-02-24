<?php

namespace Modules\Currency\Application\UseCases;

use DomainException;
use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;

class ConvertAmountUseCase
{
    public function __construct(
        private ExchangeRateRepositoryInterface $exchangeRateRepo,
    ) {}

    /**
     * Convert an amount from one currency to another using the latest exchange rate.
     *
     * @return string BCMath-formatted converted amount (scale 8)
     */
    public function execute(string $tenantId, string $fromCode, string $toCode, string $amount): string
    {
        $fromCode = strtoupper(trim($fromCode));
        $toCode   = strtoupper(trim($toCode));

        if ($fromCode === $toCode) {
            return bcadd($amount, '0', 8);
        }

        $exchangeRate = $this->exchangeRateRepo->findLatest($tenantId, $fromCode, $toCode);

        if (! $exchangeRate) {
            throw new DomainException("No exchange rate found for {$fromCode} → {$toCode}.");
        }

        return bcmul($amount, $exchangeRate->rate, 8);
    }
}
