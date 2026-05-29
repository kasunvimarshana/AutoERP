import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { getBootstrap } from '../config/bootstrap';
import type { AppUser } from '../types/app';

interface AuthContextValue {
    user: AppUser | null;
    setUser: (user: AppUser | null) => void;
    signOut: () => void;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
    const bootstrap = getBootstrap();
    const [user, setUser] = useState<AppUser | null>(bootstrap.user);

    const value = useMemo<AuthContextValue>(
        () => ({
            user,
            setUser,
            signOut: () => setUser(null),
        }),
        [user],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}
