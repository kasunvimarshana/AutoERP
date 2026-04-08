<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\RepositoryInterfaces;

use Modules\Currency\Domain\Entities\ExchangeRate;

interface ExchangeRateRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ExchangeRate;

    public function findByPair(string $tenantId, string $from, string $to, ?string $date = null): ?ExchangeRate;

    /** @return ExchangeRate[] */
    public function findAll(string $tenantId): array;

    public function save(ExchangeRate $rate): void;

    public function delete(string $tenantId, string $id): void;
}
