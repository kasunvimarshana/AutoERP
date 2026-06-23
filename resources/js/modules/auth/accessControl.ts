export const SUPER_ADMIN_ROLE = 'super admin';

export interface AccessSubject {
    roles: string[];
    permissions: string[];
    permissionsLoaded: boolean;
}

export interface AccessRequirement {
    permissions?: readonly string[];
    roles?: readonly string[];
}

export function normalizeAccessValue(value: string): string {
    return value.trim().toLowerCase();
}

export function isSuperAdmin(roles: string[]): boolean {
    return roles.some((role) => normalizeAccessValue(role) === SUPER_ADMIN_ROLE);
}

export function hasPermission(subject: AccessSubject, permission: string): boolean {
    if (!subject.permissionsLoaded) return false;
    if (isSuperAdmin(subject.roles)) return true;

    const required = normalizeAccessValue(permission);
    return subject.permissions.some((candidate) => normalizeAccessValue(candidate) === required);
}

export function meetsAccessRequirement(subject: AccessSubject, requirement: AccessRequirement): boolean {
    if (isSuperAdmin(subject.roles)) return true;

    const requiredPermissions = requirement.permissions ?? [];
    const requiredRoles = requirement.roles ?? [];
    if (requiredPermissions.length > 0 && !subject.permissionsLoaded) return false;
    if (requiredPermissions.length === 0 && requiredRoles.length === 0) return true;

    const permissions = new Set(subject.permissions.map(normalizeAccessValue));
    const roles = new Set(subject.roles.map(normalizeAccessValue));

    return requiredPermissions.some((permission) => permissions.has(normalizeAccessValue(permission)))
        || requiredRoles.some((role) => roles.has(normalizeAccessValue(role)));
}
