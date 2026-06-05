import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

type ThemeContextValue = {
    theme: 'light';
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeContextProvider({ children }: { children: ReactNode }) {
    const [theme] = useState<'light'>('light');
    const value = useMemo(() => ({ theme }), [theme]);

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useThemeContext() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useThemeContext must be used inside ThemeContextProvider.');
    }

    return context;
}
