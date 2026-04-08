<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Currency\Application\Contracts\ConvertAmountServiceInterface;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;

class ConvertAmountService implements ConvertAmountServiceInterface
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $exchangeRateRepository,
    ) {}

    public function convert(
        string $tenantId,
        float $amount,
        string $from,
        string $to,
        ?string $date = null,
    ): float {
        if (strtoupper($from) === strtoupper($to)) {
            return $amount;
        }

        $directRate = $this->exchangeRateRepository->findByPair($tenantId, strtoupper($from), strtoupper($to), $date);
        if ($directRate !== null) {
            return $directRate->convert($amount);
        }

        $inverseRate = $this->exchangeRateRepository->findByPair($tenantId, strtoupper($to), strtoupper($from), $date);
        if ($inverseRate !== null) {
            $inverse = $inverseRate->getInverseRate();
            if (abs($inverse) < PHP_FLOAT_EPSILON) {
                throw new \RuntimeException("Exchange rate for [{$from}→{$to}] is effectively zero.");
            }

            return $amount * $inverse;
        }

        throw new NotFoundException("No exchange rate found for [{$from}→{$to}].");
    }
}
