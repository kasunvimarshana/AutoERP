<?php
declare(strict_types=1);
namespace Modules\Currency\Application\Services;

use Modules\Currency\Domain\Entities\Currency;
use Modules\Currency\Domain\Exceptions\CurrencyNotFoundException;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;

class ManageCurrencyService
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $repository,
    ) {}

    public function findByCode(string $code): Currency
    {
        $currency = $this->repository->findByCode($code);
        if ($currency === null) {
            throw new CurrencyNotFoundException($code);
        }
        return $currency;
    }

    public function findAll(bool $activeOnly = true): array
    {
        return $this->repository->findAll($activeOnly);
    }

    public function findBaseCurrency(): Currency
    {
        $currency = $this->repository->findBaseCurrency();
        if ($currency === null) {
            throw new CurrencyNotFoundException('base');
        }
        return $currency;
    }

    public function create(array $data): Currency
    {
        return $this->repository->create($data);
    }

    public function update(string $code, array $data): Currency
    {
        $this->findByCode($code);
        return $this->repository->update($code, $data) ?? $this->findByCode($code);
    }

    public function activate(string $code): Currency
    {
        $this->findByCode($code);
        return $this->repository->update($code, ['is_active' => true]) ?? $this->findByCode($code);
    }

    public function deactivate(string $code): Currency
    {
        $this->findByCode($code);
        return $this->repository->update($code, ['is_active' => false]) ?? $this->findByCode($code);
    }
}
