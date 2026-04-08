<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Currency\Domain\Entities\Currency;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;

class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Currency
    {
        $model = CurrencyModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByCode(string $tenantId, string $code): ?Currency
    {
        $model = CurrencyModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return CurrencyModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(CurrencyModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findActive(string $tenantId): array
    {
        return CurrencyModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn(CurrencyModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Currency $currency): void
    {
        /** @var CurrencyModel $model */
        $model = CurrencyModel::withoutGlobalScopes()->findOrNew($currency->id);

        $model->fill([
            'tenant_id'      => $currency->tenantId,
            'code'           => $currency->code,
            'name'           => $currency->name,
            'symbol'         => $currency->symbol,
            'decimal_places' => $currency->decimalPlaces,
            'is_base'        => $currency->isBase,
            'is_active'      => $currency->isActive,
        ]);

        if (! $model->exists) {
            $model->id = $currency->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        CurrencyModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(CurrencyModel $model): Currency
    {
        return new Currency(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            code: (string) $model->code,
            name: (string) $model->name,
            symbol: (string) $model->symbol,
            decimalPlaces: (int) $model->decimal_places,
            isBase: (bool) $model->is_base,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
