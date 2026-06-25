<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\Enums\ItemType;
use Modules\Tenant\Models\TenantModel;

final class ItemUsageModuleCatalogue
{
    private const SUPPORTED = [
        'inventory' => [ItemType::Stock, ItemType::Consumable, ItemType::Asset],
        'purchase' => [ItemType::Stock, ItemType::NonStock, ItemType::Service, ItemType::Labour, ItemType::Asset, ItemType::Consumable],
        'sales' => [ItemType::Stock, ItemType::NonStock, ItemType::Service, ItemType::Labour, ItemType::Asset, ItemType::Consumable, ItemType::Package, ItemType::Combo],
        'invoice' => [ItemType::Stock, ItemType::NonStock, ItemType::Service, ItemType::Labour, ItemType::Asset, ItemType::Consumable, ItemType::Package, ItemType::Combo],
        'vehicle-service' => [ItemType::Stock, ItemType::Service, ItemType::Labour, ItemType::Consumable, ItemType::Package, ItemType::Combo],
        'vehicle-rental' => [ItemType::Service, ItemType::Asset, ItemType::Package],
    ];

    /**
     * @return list<string>
     */
    public function supportedModules(): array
    {
        return array_keys(self::SUPPORTED);
    }

    /**
     * @return list<array{code: string, name: string, supported_item_types: list<string>}>
     */
    public function enabledModules(int $tenantId): array
    {
        $modules = [];
        foreach (self::SUPPORTED as $code => $itemTypes) {
            if (! $this->isEnabledForTenant($tenantId, $code)) {
                continue;
            }

            $modules[] = [
                'code' => $code,
                'name' => ucwords(str_replace('-', ' ', $code)),
                'supported_item_types' => array_map(
                    static fn (ItemType $itemType): string => $itemType->value,
                    $itemTypes,
                ),
            ];
        }

        return $modules;
    }

    public function isEnabledForTenant(int $tenantId, string $moduleCode): bool
    {
        if (! array_key_exists($moduleCode, self::SUPPORTED)) {
            return false;
        }

        $tenant = TenantModel::query()->with('plan')->find($tenantId);
        $features = $tenant?->plan?->features;
        if (! is_array($features)) {
            return true;
        }

        $configured = $features['enabled_modules'] ?? $features['modules'] ?? null;
        if (! is_array($configured)) {
            return true;
        }

        $enabled = [];
        foreach ($configured as $key => $value) {
            if (is_int($key) && is_scalar($value)) {
                $enabled[] = strtolower(trim((string) $value));

                continue;
            }

            if (is_string($key) && filter_var($value, FILTER_VALIDATE_BOOL)) {
                $enabled[] = strtolower(trim($key));
            }
        }

        return in_array($moduleCode, array_values(array_unique($enabled)), true);
    }

    public function supportsItemType(string $moduleCode, ItemType $itemType): bool
    {
        return in_array($itemType, self::SUPPORTED[$moduleCode] ?? [], true);
    }
}
