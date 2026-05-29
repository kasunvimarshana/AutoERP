import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

interface UiContextValue {
    sidebarCollapsed: boolean;
    commandPaletteOpen: boolean;
    toggleSidebar: () => void;
    setCommandPaletteOpen: (open: boolean) => void;
}

const UiContext = createContext<UiContextValue | undefined>(undefined);

export function UiProvider({ children }: { children: ReactNode }) {
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const [commandPaletteOpen, setCommandPaletteOpen] = useState(false);

    const value = useMemo<UiContextValue>(
        () => ({
            sidebarCollapsed,
            commandPaletteOpen,
            toggleSidebar: () => setSidebarCollapsed((current) => !current),
            setCommandPaletteOpen,
        }),
        [sidebarCollapsed, commandPaletteOpen],
    );

    return <UiContext.Provider value={value}>{children}</UiContext.Provider>;
}

export function useUi() {
    const context = useContext(UiContext);

    if (!context) {
        throw new Error('useUi must be used within UiProvider');
    }

    return context;
}
