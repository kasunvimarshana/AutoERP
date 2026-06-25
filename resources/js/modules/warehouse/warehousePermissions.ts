export const warehousePermissions = {
    warehousesView: 'warehouse.view',
    warehousesCreate: 'warehouse.create',
    warehousesUpdate: 'warehouse.update',
    warehousesActivate: 'warehouse.activate',
    warehousesDeactivate: 'warehouse.deactivate',
    warehousesDelete: 'warehouse.delete',
    warehousesManageDefaults: 'warehouse.defaults.manage',
    locationsView: 'warehouse.locations.view',
    locationsCreate: 'warehouse.locations.create',
    locationsUpdate: 'warehouse.locations.update',
    locationsActivate: 'warehouse.locations.activate',
    locationsDeactivate: 'warehouse.locations.deactivate',
    locationsDelete: 'warehouse.locations.delete',
    locationsManageDefaults: 'warehouse.locations.defaults.manage',
} as const;

export function hasWarehousePermission(permissions: string[], permission: string): boolean {
    return permissions.length === 0 || permissions.includes(permission);
}
