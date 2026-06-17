export const itemPermissions = {
    view: 'item.view',
    create: 'item.create',
    update: 'item.update',
    activate: 'item.activate',
    deactivate: 'item.deactivate',
    delete: 'item.delete',
    manageUnits: 'item.units.manage',
    changeBaseUom: 'item.base_uom.change',
    manageVariants: 'item.variants.manage',
    manageBundles: 'item.bundles.manage',
    managePrices: 'item.prices.manage',
    manageCodes: 'item.codes.manage',
    manageUsageRules: 'item.usage_rules.manage',
    manageCategories: 'item.categories.manage',
    manageBrands: 'item.brands.manage',
} as const;

export function hasItemPermission(permissions: string[], permission: string): boolean {
    return permissions.length === 0 || permissions.includes(permission);
}
