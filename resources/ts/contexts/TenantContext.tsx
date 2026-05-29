import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

type TenantContextValue = {
    organizationUnitName: string;
    tenantName: string;
};

const TenantContext = createContext<TenantContextValue | null>(null);

export function TenantContextProvider({ children }: { children: ReactNode }) {
    const [tenantName] = useState('Enterprise Fleet');
    const [organizationUnitName] = useState('Main Workshop');
    const value = useMemo(() => ({ organizationUnitName, tenantName }), [organizationUnitName, tenantName]);

    return <TenantContext.Provider value={value}>{children}</TenantContext.Provider>;
}

export function useTenantContext() {
    const context = useContext(TenantContext);

    if (!context) {
        throw new Error('useTenantContext must be used inside TenantContextProvider.');
    }

    return context;
}
