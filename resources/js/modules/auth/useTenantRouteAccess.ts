import { resolveTenantRouteEntitlement } from '@/app/access/resolvedRouteEntitlements';
import { parseEnabledTenantModules } from '@/app/access/tenantModules';
import { meetsAccessRequirement } from './accessControl';
import { useAuth } from './AuthProvider';

export function useTenantRouteAccess(target: string): boolean {
    const auth = useAuth();
    const pathname = target.split('?')[0];
    const entitlement = resolveTenantRouteEntitlement(pathname);

    if (entitlement === null) return false;
    if (entitlement.requiresOrganizationUnit && !auth.organizationUnit) return false;

    if (entitlement.modules?.length) {
        if (!auth.enabledModulesLoaded) return false;
        const enabled = parseEnabledTenantModules(auth.enabledModules);
        if (!enabled || entitlement.modules.some((module) => !enabled.has(module))) return false;
    }

    if (entitlement.permissions?.length || entitlement.roles?.length) {
        if (!auth.permissionsLoaded) return false;
        if (!meetsAccessRequirement(auth, entitlement)) return false;
    }

    return true;
}
