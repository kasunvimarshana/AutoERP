import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

export interface AppContextValue {
    accessToken: string | null;
    tenantId: number | null;
    organizationUnitId: number | null;
    setAccessToken: (value: string | null) => void;
    setScope: (tenantId: number | null, organizationUnitId: number | null) => void;
}

const TOKEN_KEY = 'autoerp.access_token';
const TENANT_KEY = 'autoerp.tenant_id';
const ORG_KEY = 'autoerp.organization_unit_id';

function storedNumber(key: string): number | null {
    const value = window.localStorage.getItem(key);
    return value && Number.isFinite(Number(value)) ? Number(value) : null;
}

const AppContext = createContext<AppContextValue | null>(null);

export function AppProviders({ children }: { children: ReactNode }) {
    const [accessTokenState, setAccessTokenState] = useState<string | null>(
        () => window.localStorage.getItem(TOKEN_KEY),
    );
    const [tenantId, setTenantId] = useState<number | null>(() => storedNumber(TENANT_KEY));
    const [organizationUnitId, setOrganizationUnitId] = useState<number | null>(() => storedNumber(ORG_KEY));

    const setAccessToken = useCallback((value: string | null) => {
        setAccessTokenState(value);
        value ? window.localStorage.setItem(TOKEN_KEY, value) : window.localStorage.removeItem(TOKEN_KEY);
    }, []);

    const setScope = useCallback((nextTenantId: number | null, nextOrganizationUnitId: number | null) => {
        setTenantId(nextTenantId);
        setOrganizationUnitId(nextOrganizationUnitId);
        nextTenantId
            ? window.localStorage.setItem(TENANT_KEY, String(nextTenantId))
            : window.localStorage.removeItem(TENANT_KEY);
        nextOrganizationUnitId
            ? window.localStorage.setItem(ORG_KEY, String(nextOrganizationUnitId))
            : window.localStorage.removeItem(ORG_KEY);
    }, []);

    const value = useMemo(() => ({
        accessToken: accessTokenState,
        tenantId,
        organizationUnitId,
        setAccessToken,
        setScope,
    }), [accessTokenState, organizationUnitId, setAccessToken, setScope, tenantId]);

    return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}

export function useAppContext(): AppContextValue {
    const context = useContext(AppContext);
    if (!context) {
        throw new Error('useAppContext must be used inside AppProviders.');
    }
    return context;
}

export function getStoredApiContext() {
    return {
        accessToken: window.localStorage.getItem(TOKEN_KEY),
        tenantId: storedNumber(TENANT_KEY),
        organizationUnitId: storedNumber(ORG_KEY),
    };
}
