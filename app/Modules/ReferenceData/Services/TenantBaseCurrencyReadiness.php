<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Services\Contracts\TenantBaseCurrencyReadinessInterface;

final class TenantBaseCurrencyReadiness implements TenantBaseCurrencyReadinessInterface
{
    public function __construct(private readonly CurrencyModel $currencies) {}

    public function isActive(?int $currencyId, bool $lockForUpdate = false): bool
    {
        if ($currencyId === null || $currencyId < 1) {
            return false;
        }

        $query = $this->currencies->newQuery()->whereKey($currencyId)->where('is_active', true);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }
}
