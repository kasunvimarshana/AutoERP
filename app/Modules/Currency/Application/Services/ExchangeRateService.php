<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Currency\Application\Contracts\ExchangeRateServiceInterface;
use Modules\Currency\Domain\Entities\ExchangeRate;
use Modules\Currency\Domain\Events\ExchangeRateCreated;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;

class ExchangeRateService implements ExchangeRateServiceInterface
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $exchangeRateRepository,
    ) {}

    public function getExchangeRate(string $tenantId, string $id): ExchangeRate
    {
        $rate = $this->exchangeRateRepository->findById($tenantId, $id);

        if ($rate === null) {
            throw new NotFoundException("ExchangeRate [{$id}] not found.");
        }

        return $rate;
    }

    public function createExchangeRate(string $tenantId, array $data): ExchangeRate
    {
        return DB::transaction(function () use ($tenantId, $data): ExchangeRate {
            $now = now();
            $rate = new ExchangeRate(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                fromCurrency: strtoupper($data['from_currency']),
                toCurrency: strtoupper($data['to_currency']),
                rate: (float) $data['rate'],
                effectiveDate: new \DateTimeImmutable($data['effective_date']),
                source: $data['source'] ?? 'manual',
                createdAt: $now,
                updatedAt: $now,
            );

            $this->exchangeRateRepository->save($rate);

            Event::dispatch(new ExchangeRateCreated($rate));

            return $rate;
        });
    }

    public function updateExchangeRate(string $tenantId, string $id, array $data): ExchangeRate
    {
        return DB::transaction(function () use ($tenantId, $id, $data): ExchangeRate {
            $existing = $this->getExchangeRate($tenantId, $id);

            $updated = new ExchangeRate(
                id: $existing->id,
                tenantId: $existing->tenantId,
                fromCurrency: isset($data['from_currency']) ? strtoupper($data['from_currency']) : $existing->fromCurrency,
                toCurrency: isset($data['to_currency']) ? strtoupper($data['to_currency']) : $existing->toCurrency,
                rate: (float) ($data['rate'] ?? $existing->rate),
                effectiveDate: isset($data['effective_date'])
                    ? new \DateTimeImmutable($data['effective_date'])
                    : $existing->effectiveDate,
                source: $data['source'] ?? $existing->source,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->exchangeRateRepository->save($updated);

            return $updated;
        });
    }

    public function deleteExchangeRate(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getExchangeRate($tenantId, $id);
            $this->exchangeRateRepository->delete($tenantId, $id);
        });
    }

    public function getAllExchangeRates(string $tenantId): array
    {
        return $this->exchangeRateRepository->findAll($tenantId);
    }
}
