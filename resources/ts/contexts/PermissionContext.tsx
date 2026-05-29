import { createContext, useContext, useMemo, type ReactNode } from 'react';

type PermissionContextValue = {
    can: (permission: string) => boolean;
    permissions: string[];
};

const PermissionContext = createContext<PermissionContextValue | null>(null);

export function PermissionContextProvider({ children }: { children: ReactNode }) {
    const permissions = useMemo(() => ['*'], []);
    const value = useMemo(
        () => ({
            can: (permission: string) => permissions.includes('*') || permissions.includes(permission),
            permissions,
        }),
        [permissions],
    );

    return <PermissionContext.Provider value={value}>{children}</PermissionContext.Provider>;
}

export function usePermissionContext() {
    const context = useContext(PermissionContext);

    if (!context) {
        throw new Error('usePermissionContext must be used inside PermissionContextProvider.');
    }

    return context;
}
