export const vehiclePermissions = {
    view: 'vehicle.view',
    create: 'vehicle.create',
    update: 'vehicle.update',
    delete: 'vehicle.delete',
    manageDocuments: 'vehicle.documents.manage',
    downloadDocuments: 'vehicle.documents.download',
    manageAttributes: 'vehicle.attributes.manage',
    changeStatus: 'vehicle.status.change',
} as const;

export function hasVehiclePermission(permissions: string[], permission: string): boolean {
    return permissions.length === 0 || permissions.includes(permission);
}
