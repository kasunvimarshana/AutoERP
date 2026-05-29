import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { getBootstrap } from '../config/bootstrap';
import type { OrganizationUnitInfo, TenantInfo } from '../types/app';

interface TenantContextValue {
    tenant: TenantInfo | null;
    organizationUnit: OrganizationUnitInfo | null;
    setTenant: (tenant: TenantInfo | null) => void;
    setOrganizationUnit: (organizationUnit: OrganizationUnitInfo | null) => void;
}

const TenantContext = createContext<TenantContextValue | undefined>(undefined);

export function TenantProvider({ children }: { children: ReactNode }) {
    const bootstrap = getBootstrap();
    const [tenant, setTenant] = useState<TenantInfo | null>(bootstrap.tenant);
    const [organizationUnit, setOrganizationUnit] = useState<OrganizationUnitInfo | null>(bootstrap.organizationUnit);

    const value = useMemo<TenantContextValue>(
        () => ({
            tenant,
            organizationUnit,
            setTenant,
            setOrganizationUnit,
        }),
        [tenant, organizationUnit],
    );

    return <TenantContext.Provider value={value}>{children}</TenantContext.Provider>;
}

export function useTenant() {
    const context = useContext(TenantContext);

    if (!context) {
        throw new Error('useTenant must be used within TenantProvider');
    }

    return context;
}
