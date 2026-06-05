import { createContext, useContext, useMemo, type ReactNode } from 'react';

type AppSettingsContextValue = {
    density: 'comfortable';
};

const AppSettingsContext = createContext<AppSettingsContextValue | null>(null);

export function AppSettingsContextProvider({ children }: { children: ReactNode }) {
    const value = useMemo(() => ({ density: 'comfortable' as const }), []);

    return <AppSettingsContext.Provider value={value}>{children}</AppSettingsContext.Provider>;
}

export function useAppSettingsContext() {
    const context = useContext(AppSettingsContext);

    if (!context) {
        throw new Error('useAppSettingsContext must be used inside AppSettingsContextProvider.');
    }

    return context;
}
