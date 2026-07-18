<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Tenancy\TenantFeature;
use Modules\Core\Tenancy\TenantPlanLimit;

use Illuminate\Validation\ValidationException;

final class TenantPlanSchema
{
    public const SCHEMA_VERSION = 1;

    /**
     * Foundation capabilities are required for every tenant workspace and are
     * never toggled by commercial subscriptions.
     *
     * @var list<string>
     */
    public const ALWAYS_ON_MODULES = [
        'auth',
        'tenant',
        'user',
        'organization-unit',
        'configuration',
        'reference-data',
        'audit',
    ];

    /** Plan-controlled commercial feature modules. @var array<string, string> */
    public const SUPPORTED_MODULES = [
        TenantFeature::CUSTOMER => 'Customers',
        TenantFeature::SUPPLIER => 'Suppliers',
        TenantFeature::HR => 'Human resources',
        TenantFeature::ITEM => 'Items',
        TenantFeature::WAREHOUSE => 'Warehouses',
        TenantFeature::INVENTORY => 'Inventory',
        TenantFeature::PURCHASE => 'Purchasing',
        TenantFeature::VEHICLE => 'Vehicles',
        TenantFeature::VEHICLE_SERVICE => 'Vehicle service',
        TenantFeature::INVOICE => 'Invoicing',
        TenantFeature::PAYMENT => 'Payments',
        TenantFeature::FINANCE => 'Finance',
        TenantFeature::REPORTING => 'Reporting',
    ];

    /** @var list<string> */
    public const SUPPORTED_LIMITS = [
        TenantPlanLimit::USERS,
        'max_organization_units',
        TenantPlanLimit::WAREHOUSES,
        TenantPlanLimit::STORAGE_MEGABYTES,
    ];


    /** @return list<array{code:string,label:string}> */
    public function commercialModuleCatalogue(): array
    {
        $catalogue = [];
        foreach (self::SUPPORTED_MODULES as $code => $label) {
            $catalogue[] = ['code' => $code, 'label' => $label];
        }

        return $catalogue;
    }

    /** @return list<string> */
    public function supportedModuleCodes(): array
    {
        return array_keys(self::SUPPORTED_MODULES);
    }

    /** @return array{enabled_modules:list<string>} */
    public function normalizeFeatures(mixed $features): array
    {
        $features = $features ?? [];
        if (! is_array($features)) {
            throw ValidationException::withMessages([
                'features' => ['Plan features must be an object.'],
            ]);
        }

        $unknown = array_diff(array_keys($features), ['enabled_modules']);
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'features' => ['Unsupported plan feature keys: '.implode(', ', $unknown).'.'],
            ]);
        }

        $configured = $features['enabled_modules'] ?? [];
        if (! is_array($configured)) {
            throw ValidationException::withMessages([
                'features.enabled_modules' => ['Enabled modules must be a list.'],
            ]);
        }

        $modules = [];
        foreach ($configured as $module) {
            if (! is_string($module)) {
                throw ValidationException::withMessages([
                    'features.enabled_modules' => ['Every enabled module must be a module code.'],
                ]);
            }

            $module = strtolower(trim($module));
            if (! array_key_exists($module, self::SUPPORTED_MODULES)) {
                throw ValidationException::withMessages([
                    'features.enabled_modules' => ["Unsupported module [{$module}]."],
                ]);
            }

            $modules[] = $module;
        }

        return ['enabled_modules' => array_values(array_unique($modules))];
    }

    public function normalizePrice(mixed $price): string
    {
        $price = is_scalar($price) ? trim((string) $price) : '';
        $price = $price === '' ? '0' : $price;
        if (preg_match('/^(?:0|[1-9]\d{0,13})(?:\.(\d{1,6}))?$/', $price, $matches) !== 1) {
            throw ValidationException::withMessages([
                'price' => ['Price must be a non-negative decimal with at most 14 whole digits and 6 decimal places.'],
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '');

        return $whole.'.'.str_pad($fraction, 6, '0');
    }

    /** @return array<string, int> */
    public function normalizeLimits(mixed $limits): array
    {
        $limits = $limits ?? [];
        if (! is_array($limits)) {
            throw ValidationException::withMessages([
                'limits' => ['Plan limits must be an object.'],
            ]);
        }

        $unknown = array_diff(array_keys($limits), self::SUPPORTED_LIMITS);
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'limits' => ['Unsupported plan limit keys: '.implode(', ', $unknown).'.'],
            ]);
        }

        $normalized = [];
        foreach ($limits as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! is_numeric($value) || (int) $value < 1 || (string) (int) $value !== trim((string) $value)) {
                throw ValidationException::withMessages([
                    "limits.{$key}" => ['Plan limits must be positive whole numbers.'],
                ]);
            }

            $normalized[(string) $key] = (int) $value;
        }

        return $normalized;
    }
}
