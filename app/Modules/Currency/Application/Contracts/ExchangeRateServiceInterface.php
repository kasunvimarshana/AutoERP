<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Contracts;

use Modules\Currency\Domain\Entities\ExchangeRate;

interface ExchangeRateServiceInterface
{
    public function getExchangeRate(string $tenantId, string $id): ExchangeRate;

    public function createExchangeRate(string $tenantId, array $data): ExchangeRate;

    public function updateExchangeRate(string $tenantId, string $id, array $data): ExchangeRate;

    public function deleteExchangeRate(string $tenantId, string $id): void;

    /** @return ExchangeRate[] */
    public function getAllExchangeRates(string $tenantId): array;
}
