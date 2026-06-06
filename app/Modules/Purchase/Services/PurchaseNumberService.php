<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;

final class PurchaseNumberService
{
    public function next(int $tenantId, string $prefix, string $table, string $column): string
    {
        $count = (int) DB::table($table)->where('tenant_id', $tenantId)->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
