import type { TenantModuleCode } from './tenantModules';

export interface TenantRouteEntitlement {
    modules?: readonly TenantModuleCode[];
    requiresOrganizationUnit?: boolean;
    permissions?: readonly string[];
    roles?: readonly string[];
}

export interface EntitlementRule extends TenantRouteEntitlement {
    path: string;
}

export const rule = (
    path: string,
    entitlement: Omit<EntitlementRule, 'path'> = {},
): EntitlementRule => ({
    path,
    ...entitlement,
});

export const operational = (
    path: string,
    modules?: readonly TenantModuleCode[],
    permissions?: readonly string[],
): EntitlementRule => rule(path, { modules, permissions, requiresOrganizationUnit: true });

export const tenant = (
    path: string,
    modules?: readonly TenantModuleCode[],
    permissions?: readonly string[],
): EntitlementRule => rule(path, { modules, permissions });
