<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\RepositoryInterfaces;

use Modules\Currency\Domain\Entities\Currency;

interface CurrencyRepositoryInterface
{
    public function findByCode(string $code): ?Currency;
    public function findAll(bool $activeOnly = true): array;
    public function findBaseCurrency(): ?Currency;
    public function create(array $data): Currency;
    public function update(string $code, array $data): ?Currency;
}
