<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\RepositoryInterfaces;

use Modules\Currency\Domain\Entities\Currency;

interface CurrencyRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Currency;

    public function findByCode(string $tenantId, string $code): ?Currency;

    /** @return Currency[] */
    public function findAll(string $tenantId): array;

    /** @return Currency[] */
    public function findActive(string $tenantId): array;

    public function save(Currency $currency): void;

    public function delete(string $tenantId, string $id): void;
}
