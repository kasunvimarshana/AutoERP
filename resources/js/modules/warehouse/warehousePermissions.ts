import { hasPermission, type AccessSubject } from '@/modules/auth/accessControl';

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

export function hasWarehousePermission(subject: AccessSubject, permission: string): boolean {
    return hasPermission(subject, permission);
}
