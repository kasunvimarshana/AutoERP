export const accessPermissions = {
    usersView: 'users.view',
    usersCreate: 'users.create',
    usersUpdate: 'users.update',
    usersDelete: 'users.delete',
    usersActivate: 'users.activate',
    usersDeactivate: 'users.deactivate',
    usersAssignRoles: 'users.assign_roles',
    usersAssignPermissions: 'users.assign_permissions',
    usersManageOrganizationAccess: 'users.manage_organization_access',
    usersManageInvitations: 'users.manage_invitations',
    userDocumentsView: 'users.documents.view',
    userDocumentsManage: 'users.documents.manage',
    userDevicesView: 'users.devices.view',
    userDevicesManage: 'users.devices.manage',
    rolesView: 'roles.view',
    rolesCreate: 'roles.create',
    rolesUpdate: 'roles.update',
    rolesDelete: 'roles.delete',
    rolesAssignPermissions: 'roles.assign_permissions',
    permissionsView: 'permissions.view',
} as const;

export const protectedAccessRoles = {
    superAdmin: 'super admin',
} as const;

export { hasPermission as hasAccessPermission } from '@/modules/auth/accessControl';
