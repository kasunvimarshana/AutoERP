<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use InvalidArgumentException;

final class TenantModuleCatalogue
{
    /**
     * Foundation capabilities are required by every tenant workspace and cannot
     * be disabled by commercial plans.
     *
     * @var array<string, string>
     */
    private const FOUNDATION_MODULES = [
        'auth' => 'Authentication',
        'tenant' => 'Tenant workspace',
        'user' => 'Users and access',
        'organization-unit' => 'Organization units',
        'configuration' => 'Configuration',
        'reference-data' => 'Reference data',
        'audit' => 'Audit',
        'sequence' => 'Number sequences',
        'uom' => 'Units of measure',
    ];

    /** @var array<string, string> */
    private const PLAN_CONTROLLED_MODULES = [
        'customer' => 'Customers',
        'supplier' => 'Suppliers',
        'item' => 'Items',
        'warehouse' => 'Warehouses',
        'inventory' => 'Inventory',
        'purchase' => 'Purchasing',
        'sales' => 'Sales',
        'vehicle' => 'Vehicles',
        'vehicle-service' => 'Vehicle service',
        'vehicle-rental' => 'Vehicle rental',
        'invoice' => 'Invoicing',
        'payment' => 'Payments',
        'finance' => 'Finance',
        'reporting' => 'Reporting',
        'tax' => 'Tax',
        'hr' => 'Human resources',
        'voucher' => 'Vouchers',
    ];

    /** @return list<string> */
    public function foundationCodes(): array
    {
        return array_keys(self::FOUNDATION_MODULES);
    }

    /** @return list<string> */
    public function planControlledCodes(): array
    {
        return array_keys(self::PLAN_CONTROLLED_MODULES);
    }

    /** @return list<string> */
    public function allCodes(): array
    {
        return [...$this->foundationCodes(), ...$this->planControlledCodes()];
    }

    /** @return list<array{code:string,label:string}> */
    public function foundationCatalogue(): array
    {
        return $this->catalogue(self::FOUNDATION_MODULES);
    }

    /** @return list<array{code:string,label:string}> */
    public function planControlledCatalogue(): array
    {
        return $this->catalogue(self::PLAN_CONTROLLED_MODULES);
    }

    public function isFoundation(string $module): bool
    {
        return array_key_exists($this->normalize($module), self::FOUNDATION_MODULES);
    }

    public function isPlanControlled(string $module): bool
    {
        return array_key_exists($this->normalize($module), self::PLAN_CONTROLLED_MODULES);
    }

    public function assertKnown(string $module): string
    {
        $module = $this->normalize($module);
        if (! in_array($module, $this->allCodes(), true)) {
            throw new InvalidArgumentException("Unknown tenant module [{$module}].");
        }

        return $module;
    }

    /** @param array<string, string> $definitions @return list<array{code:string,label:string}> */
    private function catalogue(array $definitions): array
    {
        $catalogue = [];
        foreach ($definitions as $code => $label) {
            $catalogue[] = ['code' => $code, 'label' => $label];
        }

        return $catalogue;
    }

    private function normalize(string $module): string
    {
        return strtolower(trim($module));
    }
}
