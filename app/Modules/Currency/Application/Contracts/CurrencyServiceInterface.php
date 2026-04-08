<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Contracts;

use Modules\Currency\Domain\Entities\Currency;

interface CurrencyServiceInterface
{
    public function getCurrency(string $tenantId, string $id): Currency;

    public function createCurrency(string $tenantId, array $data): Currency;

    public function updateCurrency(string $tenantId, string $id, array $data): Currency;

    public function deleteCurrency(string $tenantId, string $id): void;

    /** @return Currency[] */
    public function getAllCurrencies(string $tenantId): array;

    /** @return Currency[] */
    public function getActiveCurrencies(string $tenantId): array;
}
