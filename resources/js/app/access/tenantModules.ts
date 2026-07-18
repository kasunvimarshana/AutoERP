export const TENANT_MODULE_CODE = {
    CUSTOMER: 'customer',
    SUPPLIER: 'supplier',
    HR: 'hr',
    ITEM: 'item',
    WAREHOUSE: 'warehouse',
    INVENTORY: 'inventory',
    PURCHASE: 'purchase',
    VEHICLE: 'vehicle',
    VEHICLE_SERVICE: 'vehicle-service',
    INVOICE: 'invoice',
    PAYMENT: 'payment',
    FINANCE: 'finance',
    REPORTING: 'reporting',
} as const;

export const TENANT_MODULES = [
    { code: TENANT_MODULE_CODE.CUSTOMER, label: 'Customers' },
    { code: TENANT_MODULE_CODE.SUPPLIER, label: 'Suppliers' },
    { code: TENANT_MODULE_CODE.HR, label: 'Human resources' },
    { code: TENANT_MODULE_CODE.ITEM, label: 'Items' },
    { code: TENANT_MODULE_CODE.WAREHOUSE, label: 'Warehouses' },
    { code: TENANT_MODULE_CODE.INVENTORY, label: 'Inventory' },
    { code: TENANT_MODULE_CODE.PURCHASE, label: 'Purchasing' },
    { code: TENANT_MODULE_CODE.VEHICLE, label: 'Vehicles' },
    { code: TENANT_MODULE_CODE.VEHICLE_SERVICE, label: 'Vehicle service' },
    { code: TENANT_MODULE_CODE.INVOICE, label: 'Invoicing' },
    { code: TENANT_MODULE_CODE.PAYMENT, label: 'Payments' },
    { code: TENANT_MODULE_CODE.FINANCE, label: 'Finance' },
    { code: TENANT_MODULE_CODE.REPORTING, label: 'Reporting' },
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