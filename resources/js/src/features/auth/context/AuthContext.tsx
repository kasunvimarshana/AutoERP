import type { PropsWithChildren } from 'react';
import { createContext, useContext, useEffect, useState } from 'react';
import { authApi } from '../../../api/auth';
import { getStoredAccessToken, registerUnauthorizedHandler, setStoredAccessToken } from '../../../api/client';
import type { AuthToken, AuthUser, LoginPayload, RegisterPayload } from '../../../types/auth';

type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated';

type AuthContextValue = {
    status: AuthStatus;
    user: AuthUser | null;
    accessToken: string | null;
    isAuthenticated: boolean;
    login: (payload: LoginPayload) => Promise<AuthUser>;
    register: (payload: RegisterPayload) => Promise<AuthUser>;
    logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

function createHydratedSession(token: AuthToken, setStatus: (status: AuthStatus) => void, setUser: (user: AuthUser | null) => void) {
    return async function hydrateSession() {
        setStoredAccessToken(token.access_token);
        const me = await authApi.me();
        setUser(me);
        setStatus('authenticated');
        return me;
    };
}

export function AuthProvider({ children }: PropsWithChildren) {
    const [status, setStatus] = useState<AuthStatus>('loading');
    const [user, setUser] = useState<AuthUser | null>(null);
    const [accessToken, setAccessToken] = useState<string | null>(() => getStoredAccessToken());

    useEffect(() => {
        registerUnauthorizedHandler(() => {
            setStoredAccessToken(null);
            setAccessToken(null);
            setUser(null);
            setStatus('unauthenticated');

            if (window.location.pathname !== '/login') {
                window.location.replace('/login');
            }
        });

        return () => {
            registerUnauthorizedHandler(null);
        };
    }, []);

    useEffect(() => {
        if (!accessToken) {
            setStatus('unauthenticated');
            return;
        }

        let active = true;

        void authApi
            .me()
            .then((currentUser) => {
                if (!active) {
                    return;
                }

                setUser(currentUser);
                setStatus('authenticated');
            })
            .catch(() => {
                if (!active) {
                    return;
                }

                setStoredAccessToken(null);
                setAccessToken(null);
                setUser(null);
                setStatus('unauthenticated');
            });

        return () => {
            active = false;
        };
    }, [accessToken]);

    async function login(payload: LoginPayload) {
        const token = await authApi.login(payload);
        setAccessToken(token.access_token);
        return createHydratedSession(token, setStatus, setUser)();
    }

    async function register(payload: RegisterPayload) {
        const token = await authApi.register(payload);
        setAccessToken(token.access_token);
        return createHydratedSession(token, setStatus, setUser)();
    }

    async function logout() {
        try {
            await authApi.logout();
        } finally {
            setStoredAccessToken(null);
            setAccessToken(null);
            setUser(null);
            setStatus('unauthenticated');
        }
    }

    return (
        <AuthContext.Provider
            value={{
                status,
                user,
                accessToken,
                isAuthenticated: status === 'authenticated' && Boolean(accessToken),
                login,
                register,
                logout,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}
