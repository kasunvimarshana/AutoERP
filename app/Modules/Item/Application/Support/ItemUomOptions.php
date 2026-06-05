<?php

declare(strict_types=1);

namespace Modules\Item\Application\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ItemUomOptions
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function allowedUoms(int $tenantId, int $itemId, ?string $context = null): array
    {
        if ($tenantId < 1 || $itemId < 1 || ! Schema::hasTable('items') || ! Schema::hasTable('unit_of_measures')) {
            return [];
        }

        $item = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('id', $itemId)
            ->first([
                'id',
                'base_uom_id',
                'default_receipt_uom_id',
                'default_issue_uom_id',
                'default_consumption_uom_id',
                'default_charge_uom_id',
            ]);

        if ($item === null) {
            return [];
        }

        $defaultUomId = self::defaultUomId($item, $context);
        $baseUomId = (int) ($item->base_uom_id ?? 0);
        $uomIds = array_filter([
            $baseUomId,
            (int) ($item->default_receipt_uom_id ?? 0),
            (int) ($item->default_issue_uom_id ?? 0),
            (int) ($item->default_consumption_uom_id ?? 0),
            (int) ($item->default_charge_uom_id ?? 0),
        ], static fn (int $id): bool => $id > 0);

        $conversionFactors = [];
        if (Schema::hasTable('uom_conversions')) {
            $conversions = DB::table('uom_conversions')
                ->where('tenant_id', $tenantId)
                ->where('item_id', $itemId)
                ->where('is_active', true)
                ->get(['from_uom_id', 'to_uom_id', 'factor', 'is_bidirectional']);

            foreach ($conversions as $conversion) {
                $fromUomId = (int) $conversion->from_uom_id;
                $toUomId = (int) $conversion->to_uom_id;
                $factor = is_numeric($conversion->factor) ? (float) $conversion->factor : null;

                $uomIds[] = $fromUomId;
                $uomIds[] = $toUomId;

                if ($factor !== null && $baseUomId > 0) {
                    if ($toUomId === $baseUomId) {
                        $conversionFactors[$fromUomId] = $factor;
                    }

                    if ($fromUomId === $baseUomId && (bool) $conversion->is_bidirectional && $factor !== 0.0) {
                        $conversionFactors[$toUomId] = 1 / $factor;
                    }
                }
            }
        }

        $uomIds = array_values(array_unique(array_filter($uomIds, static fn (int $id): bool => $id > 0)));
        if ($uomIds === []) {
            return [];
        }

        $query = DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $uomIds)
            ->where('is_active', true);

        $contextColumn = self::contextColumn($context);
        if ($contextColumn !== null && Schema::hasColumn('unit_of_measures', $contextColumn)) {
            $query->where($contextColumn, true);
        }

        return $query
            ->orderByRaw('CASE WHEN id = ? THEN 0 WHEN id = ? THEN 1 ELSE 2 END', [$defaultUomId ?: $baseUomId, $baseUomId])
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'symbol',
                'name',
                'category',
                'type',
                'is_base',
                'usable_for_inventory',
                'usable_for_purchase',
                'usable_for_sales',
                'usable_for_service',
                'usable_for_rental',
            ])
            ->map(static function (object $uom) use ($baseUomId, $defaultUomId, $conversionFactors, $context): array {
                $id = (int) $uom->id;

                return [
                    'id' => $id,
                    'code' => $uom->code,
                    'symbol' => $uom->symbol,
                    'name' => $uom->name,
                    'category' => $uom->category ?? $uom->type,
                    'is_base' => $id === $baseUomId,
                    'is_default_for_context' => $defaultUomId > 0 ? $id === $defaultUomId : $id === $baseUomId,
                    'conversion_factor' => $id === $baseUomId ? 1.0 : ($conversionFactors[$id] ?? null),
                    'allowed_contexts' => self::allowedContextsForUnit($uom),
                    'context' => $context,
                ];
            })
            ->values()
            ->all();
    }

    public static function isAllowed(int $tenantId, int $itemId, int $uomId, ?string $context = null): bool
    {
        foreach (self::allowedUoms($tenantId, $itemId, $context) as $uom) {
            if ((int) $uom['id'] === $uomId) {
                return true;
            }
        }

        return false;
    }

    private static function defaultUomId(object $item, ?string $context): int
    {
        return match (self::normalizedContext($context)) {
            'purchase' => (int) ($item->default_receipt_uom_id ?? 0),
            'sales' => (int) ($item->default_issue_uom_id ?? 0),
            'service', 'inventory' => (int) ($item->default_consumption_uom_id ?? 0),
            'rental' => (int) ($item->default_charge_uom_id ?? 0),
            default => 0,
        } ?: (int) ($item->base_uom_id ?? 0);
    }

    private static function contextColumn(?string $context): ?string
    {
        return match (self::normalizedContext($context)) {
            'inventory' => 'usable_for_inventory',
            'purchase' => 'usable_for_purchase',
            'sales' => 'usable_for_sales',
            'service' => 'usable_for_service',
            'rental' => 'usable_for_rental',
            default => null,
        };
    }

    private static function normalizedContext(?string $context): ?string
    {
        $context = strtolower(trim((string) $context));

        return $context === '' ? null : match ($context) {
            'vehicle_service' => 'service',
            'vehicle_rental' => 'rental',
            default => $context,
        };
    }

    /**
     * @return list<string>
     */
    private static function allowedContextsForUnit(object $uom): array
    {
        $contexts = [];
        foreach ([
            'inventory' => 'usable_for_inventory',
            'purchase' => 'usable_for_purchase',
            'sales' => 'usable_for_sales',
            'service' => 'usable_for_service',
            'rental' => 'usable_for_rental',
        ] as $context => $column) {
            if (property_exists($uom, $column) && (bool) $uom->{$column}) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }
}
