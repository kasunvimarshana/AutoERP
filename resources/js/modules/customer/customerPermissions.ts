export const customerPermissions = {
    view: 'customers.view',
    create: 'customers.create',
    update: 'customers.update',
    delete: 'customers.delete',
    vehiclesView: 'customer-vehicles.view',
    vehiclesCreate: 'customer-vehicles.create',
    vehiclesUpdate: 'customer-vehicles.update',
    vehiclesSetCurrent: 'customer-vehicles.set-current',
    vehiclesClearCurrent: 'customer-vehicles.clear-current',
    vehiclesDelete: 'customer-vehicles.delete',
} as const;
