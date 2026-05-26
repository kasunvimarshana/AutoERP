import type { PropsWithChildren } from 'react';
import { createContext, useContext, useEffect, useState } from 'react';
import { getStoredTenantId, setStoredTenantId } from '../../../api/client';

type TenantContextValue = {
    tenantId: number;
    setTenantId: (tenantId: number) => void;
};

const TenantContext = createContext<TenantContextValue | undefined>(undefined);

export function TenantProvider({ children }: PropsWithChildren) {
    const storedTenantId = getStoredTenantId();
    const [tenantId, setTenantIdState] = useState<number>(storedTenantId ? Number(storedTenantId) : 1);

    useEffect(() => {
        setStoredTenantId(String(tenantId));
    }, [tenantId]);

    function setTenantId(nextTenantId: number) {
        setTenantIdState(nextTenantId);
    }

    return <TenantContext.Provider value={{ tenantId, setTenantId }}>{children}</TenantContext.Provider>;
}

export function useTenant() {
    const context = useContext(TenantContext);

    if (!context) {
        throw new Error('useTenant must be used within TenantProvider');
    }

    return context;
}
