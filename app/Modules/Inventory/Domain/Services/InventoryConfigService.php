<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\Warehouse;

class InventoryConfigService
{
    public function valuationEnabled(int $tenantId, ?int $organizationUnitId, ?int $warehouseId): bool
    {
        return $this->featureEnabled('valuation', $tenantId, $organizationUnitId, $warehouseId);
    }

    public function allocationEnabled(int $tenantId, ?int $organizationUnitId, ?int $warehouseId): bool
    {
        return $this->featureEnabled('allocation', $tenantId, $organizationUnitId, $warehouseId);
    }

    public function retryAttempts(): int
    {
        return (int) config('inventory-engine.retries.attempts', 3);
    }

    private function featureEnabled(string $feature, int $tenantId, ?int $organizationUnitId, ?int $warehouseId): bool
    {
        $keys = config("inventory-engine.feature_keys.{$feature}", []);
        $default = (bool) config("inventory-engine.defaults.{$feature}_enabled", true);

        foreach ((array) $keys as $key) {
            $tenantValue = DB::table('tenant_settings')
                ->where('tenant_id', $tenantId)
                ->where('key', $key)
                ->value('value');
            if ($tenantValue !== null) {
                $default = $this->toBool($tenantValue, $default);
            }

            if ($organizationUnitId !== null) {
                $orgValue = DB::table('organization_unit_settings')
                    ->where('tenant_id', $tenantId)
                    ->where('organization_unit_id', $organizationUnitId)
                    ->where('key', $key)
                    ->value('value');
                if ($orgValue !== null) {
                    $default = $this->toBool($orgValue, $default);
                }
            }
        }

        if ($warehouseId !== null) {
            $warehouse = Warehouse::query()->find($warehouseId);
            if ($warehouse !== null) {
                $metaValue = data_get($warehouse->metadata ?? [], "inventory.features.{$feature}");
                if ($metaValue !== null) {
                    $default = $this->toBool($metaValue, $default);
                }
            }
        }

        return $default;
    }

    private function toBool(mixed $value, bool $fallback): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off', 'disabled'], true)) {
                return false;
            }
        }

        return $fallback;
    }
}
