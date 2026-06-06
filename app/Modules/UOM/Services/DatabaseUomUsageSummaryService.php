<?php

declare(strict_types=1);

namespace Modules\UOM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\UOM\Contracts\Services\UomUsageSummaryServiceInterface;

final class DatabaseUomUsageSummaryService implements UomUsageSummaryServiceInterface
{
    /**
     * @return array<string, int>
     */
    public function summarize(int $unitId, int $tenantId): array
    {
        return [
            'conversions_from' => $this->countReferences($tenantId, $unitId, 'from_uom_id'),
            'conversions_to' => $this->countReferences($tenantId, $unitId, 'to_uom_id'),
        ];
    }

    private function countReferences(int $tenantId, int $unitId, string $column): int
    {
        if (! Schema::hasTable('uom_conversions') || ! Schema::hasColumn('uom_conversions', $column)) {
            return 0;
        }

        return (int) DB::table('uom_conversions')
            ->where('tenant_id', $tenantId)
            ->where($column, $unitId)
            ->count();
    }
}
