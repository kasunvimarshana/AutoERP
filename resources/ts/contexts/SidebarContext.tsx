import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

type SidebarContextValue = {
    closeSidebar: () => void;
    isSidebarOpen: boolean;
    toggleSidebar: () => void;
};

const SidebarContext = createContext<SidebarContextValue | null>(null);

export function SidebarContextProvider({ children }: { children: ReactNode }) {
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const value = useMemo(
        () => ({
            closeSidebar: () => setIsSidebarOpen(false),
            isSidebarOpen,
            toggleSidebar: () => setIsSidebarOpen((current) => !current),
        }),
        [isSidebarOpen],
    );

    return <SidebarContext.Provider value={value}>{children}</SidebarContext.Provider>;
}

export function useSidebarContext() {
    const context = useContext(SidebarContext);

    if (!context) {
        throw new Error('useSidebarContext must be used inside SidebarContextProvider.');
    }

    return context;
}
