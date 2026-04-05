<?php
declare(strict_types=1);
namespace Modules\Currency\Application\Services;

use Modules\Currency\Domain\Exceptions\ExchangeRateNotFoundException;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;

/**
 * Converts an amount from one currency to another using the stored exchange rates.
 * If from == to, returns the original amount unchanged.
 * Supports triangulation through a base currency if no direct rate is found.
 */
class ConvertAmountService
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $rateRepository,
    ) {}

    public function convert(
        int $tenantId,
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        ?\DateTimeInterface $at = null,
    ): float {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $at ??= new \DateTimeImmutable();

        $rate = $this->rateRepository->findActive($tenantId, $fromCurrency, $toCurrency, $at);
        if ($rate !== null) {
            return round($rate->convert($amount), 10);
        }

        // Try inverse rate
        $inverseRate = $this->rateRepository->findActive($tenantId, $toCurrency, $fromCurrency, $at);
        if ($inverseRate !== null) {
            return round($amount * $inverseRate->invertedRate(), 10);
        }

        throw new ExchangeRateNotFoundException($fromCurrency, $toCurrency);
    }
}
