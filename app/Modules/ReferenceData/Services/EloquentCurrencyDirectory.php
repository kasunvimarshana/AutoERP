<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;
use Modules\ReferenceData\Models\CurrencyModel;

final class EloquentCurrencyDirectory implements CurrencyDirectoryInterface
{
    public function __construct(private readonly CurrencyModel $currencies) {}

    public function find(?int $currencyId, bool $lockForUpdate = false): ?array
    {
        if ($currencyId === null || $currencyId < 1) {
            return null;
        }

        $query = $this->currencies->newQuery()
            ->select(['id', 'code', 'name', 'symbol', 'is_active'])
            ->whereKey($currencyId);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $currency = $query->first();
        if (! $currency instanceof CurrencyModel) {
            return null;
        }

        return [
            'id' => (int) $currency->getKey(),
            'code' => (string) $currency->getAttribute('code'),
            'name' => (string) $currency->getAttribute('name'),
            'symbol' => $currency->getAttribute('symbol') === null ? null : (string) $currency->getAttribute('symbol'),
            'is_active' => (bool) $currency->getAttribute('is_active'),
        ];
    }

    public function isActive(?int $currencyId, bool $lockForUpdate = false): bool
    {
        return (bool) ($this->find($currencyId, $lockForUpdate)['is_active'] ?? false);
    }
}
