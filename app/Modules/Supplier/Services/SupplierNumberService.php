<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Modules\Supplier\Models\Supplier;

final class SupplierNumberService
{
    public function next(int $tenantId): string
    {
        $next = Supplier::query()->withTrashed()->where('tenant_id', $tenantId)->count() + 1;

        return 'SUP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
