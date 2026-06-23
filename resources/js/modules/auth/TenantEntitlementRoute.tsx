import { Outlet, useLocation } from 'react-router-dom';
import { resolveTenantRouteEntitlement } from '@/app/access/routeEntitlements';
import { parseEnabledTenantModules } from '@/app/access/tenantModules';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { ModuleUnavailablePage } from '@/app/errors/ModuleUnavailablePage';
import { OrganizationUnitRequiredPage } from '@/app/errors/OrganizationUnitRequiredPage';
import { LoadingState } from '@/shared/components/LoadingState';
import { meetsAccessRequirement } from './accessControl';
import { useAuth } from './AuthProvider';

export function TenantEntitlementRoute() {
    const auth = useAuth();
    const location = useLocation();
    const entitlement = resolveTenantRouteEntitlement(location.pathname);

    if (entitlement === null) {
        return (
            <AccessDeniedPage
                title="Page unavailable"
                message="This page does not have a configured access policy. Contact an administrator."
            />
        );
    }

    if (entitlement.requiresOrganizationUnit && !auth.organizationUnit) {
        return <OrganizationUnitRequiredPage />;
    }

    if (entitlement.modules && entitlement.modules.length > 0) {
        if (!auth.enabledModulesLoaded) {
            return <LoadingState label="Checking tenant plan..." fullPage />;
        }

        const enabled = parseEnabledTenantModules(auth.enabledModules);
        const missing = entitlement.modules.filter((module) => !enabled?.has(module));
        if (missing.length > 0) {
            return <ModuleUnavailablePage modules={missing} />;
        }
    }

    if ((entitlement.permissions?.length ?? 0) > 0 || (entitlement.roles?.length ?? 0) > 0) {
        if (!auth.permissionsLoaded) {
            return <LoadingState label="Checking access..." fullPage />;
        }

        if (!meetsAccessRequirement(auth, entitlement)) {
            return <AccessDeniedPage />;
        }
    }

    return <Outlet />;
}
