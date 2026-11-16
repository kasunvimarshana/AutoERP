<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;

final class ServiceSalesDashboardService
{
    private const TOP_ITEM_LIMIT = 5;

    public function __construct(
        private readonly VehicleServiceSalesSummaryReportService $sales,
        private readonly DecimalMath $math,
    ) {}

    /** @return array<string, mixed> */
    public function run(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $report = $this->sales->run([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return [
            'by_week' => $this->weeklyBuckets($report['by_week'], $dateFrom, $dateTo),
            'by_job_type' => $report['summary']['by_job_type'],
            'top_stock_items' => $this->topStockItems(
                $tenantId,
                $organizationUnitId,
                $report['summary']['stock']['items'],
            ),
        ];
    }

    /** @param list<array<string, mixed>> $weeks @return list<array<string, mixed>> */
    private function weeklyBuckets(array $weeks, string $dateFrom, string $dateTo): array
    {
        $byDate = array_column($weeks, null, 'week_start');
        $cursor = CarbonImmutable::parse($dateFrom)->startOfWeek();
        $end = CarbonImmutable::parse($dateTo)->startOfWeek();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $date = $cursor->toDateString();
            $buckets[] = $byDate[$date] ?? ['week_start' => $date, 'job_count' => 0, 'revenue' => '0'];
            $cursor = $cursor->addWeek();
        }

        return $buckets;
    }

    /** @param list<array<string, mixed>> $rankedItems @return list<array<string, mixed>> */
    private function topStockItems(int $tenantId, ?int $organizationUnitId, array $rankedItems): array
    {
        $items = array_slice(array_values(array_filter(
            $rankedItems,
            fn (array $item): bool => $item['item']['id'] !== null
                && $this->math->compare((string) $item['quantity'], '0') > 0,
        )), 0, self::TOP_ITEM_LIMIT);
        if ($items === []) {
            return [];
        }

        $ids = array_column(array_column($items, 'item'), 'id');
        $reorderLevels = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('reorder_level', 'id');
        $balances = DB::table('inventory_stock_balances')
            ->where('tenant_id', $tenantId)
            ->whereIn('item_id', $ids);
        $organizationUnitId === null
            ? $balances->whereNull('organization_unit_id')
            : $balances->where('organization_unit_id', $organizationUnitId);
        $available = $balances
            ->select('item_id')
            ->selectRaw('SUM(quantity_available) as quantity')
            ->groupBy('item_id')
            ->pluck('quantity', 'item_id');

        return array_map(function (array $ranked) use ($available, $reorderLevels): array {
            $item = $ranked['item'];
            $id = $item['id'];
            $quantityAvailable = (string) ($available[$id] ?? '0');
            $reorderLevel = $reorderLevels[$id] ?? null;

            return [
                'item' => $item,
                'quantity_sold' => $ranked['quantity'],
                'revenue' => $ranked['sales_amount'],
                'quantity_available' => $quantityAvailable,
                'reorder_level' => $reorderLevel === null ? null : (string) $reorderLevel,
                'needs_reorder' => $reorderLevel !== null
                    && $this->math->compare($quantityAvailable, (string) $reorderLevel) <= 0,
            ];
        }, $items);
    }
}
