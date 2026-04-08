<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Currency\Application\Contracts\CurrencyServiceInterface;
use Modules\Currency\Domain\Entities\Currency;
use Modules\Currency\Domain\Events\CurrencyCreated;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;

class CurrencyService implements CurrencyServiceInterface
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {}

    public function getCurrency(string $tenantId, string $id): Currency
    {
        $currency = $this->currencyRepository->findById($tenantId, $id);

        if ($currency === null) {
            throw new NotFoundException("Currency [{$id}] not found.");
        }

        return $currency;
    }

    public function createCurrency(string $tenantId, array $data): Currency
    {
        return DB::transaction(function () use ($tenantId, $data): Currency {
            $now = now();
            $currency = new Currency(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                code: strtoupper($data['code']),
                name: $data['name'],
                symbol: $data['symbol'],
                decimalPlaces: (int) ($data['decimal_places'] ?? 2),
                isBase: (bool) ($data['is_base'] ?? false),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->currencyRepository->save($currency);

            Event::dispatch(new CurrencyCreated($currency));

            return $currency;
        });
    }

    public function updateCurrency(string $tenantId, string $id, array $data): Currency
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Currency {
            $existing = $this->getCurrency($tenantId, $id);

            $updated = new Currency(
                id: $existing->id,
                tenantId: $existing->tenantId,
                code: isset($data['code']) ? strtoupper($data['code']) : $existing->code,
                name: $data['name'] ?? $existing->name,
                symbol: $data['symbol'] ?? $existing->symbol,
                decimalPlaces: (int) ($data['decimal_places'] ?? $existing->decimalPlaces),
                isBase: (bool) ($data['is_base'] ?? $existing->isBase),
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->currencyRepository->save($updated);

            return $updated;
        });
    }

    public function deleteCurrency(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getCurrency($tenantId, $id);
            $this->currencyRepository->delete($tenantId, $id);
        });
    }

    public function getAllCurrencies(string $tenantId): array
    {
        return $this->currencyRepository->findAll($tenantId);
    }

    public function getActiveCurrencies(string $tenantId): array
    {
        return $this->currencyRepository->findActive($tenantId);
    }
}
