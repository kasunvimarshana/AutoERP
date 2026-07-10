export const TENANT_MODULES = [
    { code: 'customer', label: 'Customers' },
    { code: 'supplier', label: 'Suppliers' },
    { code: 'hr', label: 'Human resources' },
    { code: 'item', label: 'Items' },
    { code: 'warehouse', label: 'Warehouses' },
    { code: 'inventory', label: 'Inventory' },
    { code: 'purchase', label: 'Purchasing' },
    { code: 'vehicle', label: 'Vehicles' },
    { code: 'vehicle-service', label: 'Vehicle service' },
    { code: 'vehicle-rental', label: 'Vehicle rental' },
    { code: 'invoice', label: 'Invoicing' },
    { code: 'payment', label: 'Payments' },
    { code: 'finance', label: 'Finance' },
    { code: 'reporting', label: 'Reporting' },
] as const;

export type TenantModuleCode = (typeof TENANT_MODULES)[number]['code'];

const supportedModules = new Set<string>(TENANT_MODULES.map((module) => module.code));

export function isTenantModuleCode(value: string): value is TenantModuleCode {
    return supportedModules.has(value);
}

export function parseEnabledTenantModules(values: readonly string[] | null): Set<TenantModuleCode> | null {
    if (values === null) return null;

    const modules = new Set<TenantModuleCode>();
    for (const raw of values) {
        const value = raw.trim().toLowerCase();
        if (isTenantModuleCode(value)) modules.add(value);
    }

    return modules;
}
