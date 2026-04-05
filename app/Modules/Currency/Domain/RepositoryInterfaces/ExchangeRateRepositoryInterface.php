<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\RepositoryInterfaces;

use Modules\Currency\Domain\Entities\ExchangeRate;

interface ExchangeRateRepositoryInterface
{
    public function findActive(int $tenantId, string $from, string $to, \DateTimeInterface $at): ?ExchangeRate;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): ExchangeRate;
    public function update(int $id, array $data): ?ExchangeRate;
    public function delete(int $id): bool;
}
