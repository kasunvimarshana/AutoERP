<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;

final class InventoryNumberService
{
    public function next(int $tenantId, string $prefix, string $table, string $column): string
    {
        $count = DB::table($table)->where('tenant_id', $tenantId)->count() + 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
