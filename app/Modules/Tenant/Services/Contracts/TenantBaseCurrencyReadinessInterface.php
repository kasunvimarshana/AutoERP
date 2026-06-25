<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantBaseCurrencyReadinessInterface
{
    public function isActive(?int $currencyId, bool $lockForUpdate = false): bool;
}
