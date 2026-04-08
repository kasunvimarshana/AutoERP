<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Currency\Domain\Entities\ExchangeRate;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Models\ExchangeRateModel;

class EloquentExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ExchangeRate
    {
        $model = ExchangeRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByPair(string $tenantId, string $from, string $to, ?string $date = null): ?ExchangeRate
    {
        $query = ExchangeRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to));

        if ($date !== null) {
            $query->where('effective_date', '<=', $date)
                  ->orderByDesc('effective_date');
        } else {
            $query->orderByDesc('effective_date');
        }

        $model = $query->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return ExchangeRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn(ExchangeRateModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(ExchangeRate $rate): void
    {
        /** @var ExchangeRateModel $model */
        $model = ExchangeRateModel::withoutGlobalScopes()->findOrNew($rate->id);

        $model->fill([
            'tenant_id'      => $rate->tenantId,
            'from_currency'  => $rate->fromCurrency,
            'to_currency'    => $rate->toCurrency,
            'rate'           => $rate->rate,
            'effective_date' => $rate->effectiveDate->format('Y-m-d'),
            'source'         => $rate->source,
        ]);

        if (! $model->exists) {
            $model->id = $rate->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        ExchangeRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(ExchangeRateModel $model): ExchangeRate
    {
        return new ExchangeRate(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            fromCurrency: (string) $model->from_currency,
            toCurrency: (string) $model->to_currency,
            rate: (float) $model->rate,
            effectiveDate: new DateTimeImmutable((string) $model->effective_date),
            source: (string) ($model->source ?? 'manual'),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
