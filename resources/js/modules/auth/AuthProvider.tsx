import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { ApiError } from '@/shared/api/apiError';
import {
    clearStoredAuthSession,
    getStoredApiContext,
    storeAuthSession,
} from '@/shared/api/authSessionStorage';
import { authApi } from './authApi';
import type { AuthOrganizationUnit, AuthTenant, AuthUser, LoginPayload } from './authTypes';

interface AuthContextValue {
    user: AuthUser | null;
    token: string | null;
    tenant: AuthTenant | null;
    organizationUnit: AuthOrganizationUnit | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (payload: LoginPayload) => Promise<void>;
    logout: () => Promise<void>;
    loadCurrentUser: (signal?: AbortSignal) => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const stored = getStoredApiContext();
    const [token, setToken] = useState<string | null>(stored.accessToken);
    const [sessionId, setSessionId] = useState<number | null>(stored.sessionId);
    const [user, setUser] = useState<AuthUser | null>(null);
    const [tenant, setTenant] = useState<AuthTenant | null>(
        stored.tenantId ? { id: stored.tenantId, name: null } : null,
    );
    const [organizationUnit, setOrganizationUnit] = useState<AuthOrganizationUnit | null>(
        stored.organizationUnitId ? { id: stored.organizationUnitId, name: null } : null,
    );
    const [isLoading, setIsLoading] = useState<boolean>(Boolean(stored.accessToken));
    const initialLoadStarted = useRef(false);

    const clearAuthState = useCallback(() => {
        clearStoredAuthSession();
        setToken(null);
        setSessionId(null);
        setUser(null);
        setTenant(null);
        setOrganizationUnit(null);
        setIsLoading(false);
    }, []);

    const applySession = useCallback((next: {
        token?: string | null;
        refresh_token?: string | null;
        session_id?: number | null;
        user: AuthUser;
        tenant: AuthTenant | null;
        organization_unit: AuthOrganizationUnit | null;
    }) => {
        if (next.token) {
            setToken(next.token);
        }
        setSessionId(next.session_id ?? null);
        setUser(next.user);
        setTenant(next.tenant);
        setOrganizationUnit(next.organization_unit);
        storeAuthSession({
            accessToken: next.token ?? token,
            refreshToken: next.refresh_token ?? getStoredApiContext().refreshToken,
            sessionId: next.session_id ?? sessionId,
            tenantId: toNumber(next.tenant?.id),
            organizationUnitId: toNumber(next.organization_unit?.id),
        });
    }, [sessionId, token]);

    const loadCurrentUser = useCallback(async (signal?: AbortSignal) => {
        if (!getStoredApiContext().accessToken) {
            clearAuthState();
            return;
        }

        setIsLoading(true);
        try {
            const current = await authApi.me(signal);
            if (signal?.aborted) return;
            applySession({
                user: current.user,
                tenant: current.tenant,
                organization_unit: current.organization_unit,
            });
        } catch (error) {
            if (signal?.aborted) return;
            if (error instanceof ApiError && error.status === 401) {
                clearAuthState();
                return;
            }
            throw error;
        } finally {
            if (!signal?.aborted) {
                setIsLoading(false);
            }
        }
    }, [applySession, clearAuthState]);

    const login = useCallback(async (payload: LoginPayload) => {
        setIsLoading(true);
        try {
            const session = await authApi.login({
                ...payload,
                device_name: payload.device_name ?? window.navigator.userAgent.slice(0, 160),
            });
            applySession(session);
        } finally {
            setIsLoading(false);
        }
    }, [applySession]);

    const logout = useCallback(async () => {
        const current = getStoredApiContext();
        try {
            if (current.accessToken) {
                await authApi.logout({
                    access_token: current.accessToken,
                    session_id: current.sessionId,
                });
            }
        } finally {
            clearAuthState();
        }
    }, [clearAuthState]);

    useEffect(() => {
        function handleUnauthorized() {
            clearAuthState();
            if (window.location.pathname !== '/login') {
                window.location.assign('/login');
            }
        }

        window.addEventListener('autoerp:auth-unauthorized', handleUnauthorized);
        return () => window.removeEventListener('autoerp:auth-unauthorized', handleUnauthorized);
    }, [clearAuthState]);

    useEffect(() => {
        if (!token || initialLoadStarted.current) return;

        initialLoadStarted.current = true;
        const controller = new AbortController();
        void loadCurrentUser(controller.signal).catch(() => undefined);

        return () => controller.abort();
    }, [loadCurrentUser, token]);

    const value = useMemo<AuthContextValue>(() => ({
        user,
        token,
        tenant,
        organizationUnit,
        isAuthenticated: Boolean(token && user),
        isLoading,
        login,
        logout,
        loadCurrentUser,
    }), [isLoading, loadCurrentUser, login, logout, organizationUnit, tenant, token, user]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used inside AuthProvider.');
    }

    return context;
}

function toNumber(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numeric = Number(value);
    return Number.isFinite(numeric) && numeric > 0 ? numeric : null;
}
