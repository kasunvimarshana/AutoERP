import { hasPermission, type AccessSubject } from '@/modules/auth/accessControl';

export const vehiclePermissions = {
    view: 'vehicle.view',
    create: 'vehicle.create',
    update: 'vehicle.update',
    delete: 'vehicle.delete',
    manageDocuments: 'vehicle.documents.manage',
    downloadDocuments: 'vehicle.documents.download',
    manageAttributes: 'vehicle.attributes.manage',
    changeStatus: 'vehicle.status.change',
    ownershipsView: 'vehicle.ownerships.view',
    ownershipsManage: 'vehicle.ownerships.manage',
} as const;

export function hasVehiclePermission(subject: AccessSubject, permission: string): boolean {
    return hasPermission(subject, permission);
}
