import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

type User = {
    name: string;
    role: string;
};

type AuthContextValue = {
    isAuthenticated: boolean;
    user: User;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthContextProvider({ children }: { children: ReactNode }) {
    const [user] = useState<User>({ name: 'John Workshop', role: 'Site Manager' });
    const value = useMemo(() => ({ isAuthenticated: true, user }), [user]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuthContext() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuthContext must be used inside AuthContextProvider.');
    }

    return context;
}
