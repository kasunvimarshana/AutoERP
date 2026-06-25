export const supplierPermissions = {
    view: 'suppliers.view',
    create: 'suppliers.create',
    update: 'suppliers.update',
    delete: 'suppliers.delete',
    vehiclesView: 'supplier-vehicles.view',
    vehiclesCreate: 'supplier-vehicles.create',
    vehiclesUpdate: 'supplier-vehicles.update',
    vehiclesSetCurrent: 'supplier-vehicles.set-current',
    vehiclesClearCurrent: 'supplier-vehicles.clear-current',
    vehiclesDelete: 'supplier-vehicles.delete',
} as const;
