import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { authApi } from '../modules/auth/services/authApi';
import type { AuthUser, LoginInput } from '../modules/auth/types/auth.types';
import { clearAuthSession, getStoredAccessToken, getStoredAuthSession, persistAuthSession, updateStoredAuthUser } from '../services/api/authTokenStorage';

type AuthContextValue = {
    error: string | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (input: LoginInput) => Promise<void>;
    logout: () => Promise<void>;
    refreshCurrentUser: () => Promise<void>;
    user: AuthUser | null;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthContextProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<AuthUser | null>(() => (getStoredAccessToken() ? getStoredAuthSession().user : null));
    const [isLoading, setIsLoading] = useState(() => Boolean(getStoredAccessToken()));
    const [error, setError] = useState<string | null>(null);

    const refreshCurrentUser = useCallback(async () => {
        if (!getStoredAccessToken()) {
            setUser(null);
            setIsLoading(false);

            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const currentUser = await authApi.getCurrentUser();
            updateStoredAuthUser(currentUser);
            setUser(currentUser);
        } catch (refreshError) {
            clearAuthSession();
            setUser(null);
            setError(refreshError instanceof Error ? refreshError.message : 'Unable to restore your session.');
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        void refreshCurrentUser();
    }, [refreshCurrentUser]);

    const login = useCallback(async (input: LoginInput) => {
        setError(null);
        const session = await authApi.login(input);
        persistAuthSession(session, input.remember);
        const currentUser = await authApi.getCurrentUser().catch(() => session.user);
        updateStoredAuthUser(currentUser);
        setUser(currentUser);
    }, []);

    const logout = useCallback(async () => {
        setError(null);

        try {
            await authApi.logout();
        } finally {
            clearAuthSession();
            setUser(null);
        }
    }, []);

    const value = useMemo(
        () => ({
            error,
            isAuthenticated: Boolean(user && getStoredAccessToken()),
            isLoading,
            login,
            logout,
            refreshCurrentUser,
            user,
        }),
        [error, isLoading, login, logout, refreshCurrentUser, user],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuthContext() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuthContext must be used inside AuthContextProvider.');
    }

    return context;
}
