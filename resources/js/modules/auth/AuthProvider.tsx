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
import type { AuthMode } from '@/shared/api/authSessionStorage';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import { authApi } from './authApi';
import type { AuthOrganizationUnit, AuthTenant, AuthUser, LoginPayload } from './authTypes';

interface AuthContextValue {
    user: AuthUser | null;
    token: string | null;
    tenant: AuthTenant | null;
    organizationUnit: AuthOrganizationUnit | null;
    roles: string[];
    permissions: string[];
    enabledModules: string[] | null;
    isPlatformOperator: boolean;
    authMode: AuthMode;
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
    const [authMode, setAuthMode] = useState<AuthMode>(stored.authMode);
    const [sessionId, setSessionId] = useState<number | null>(stored.sessionId);
    const [user, setUser] = useState<AuthUser | null>(null);
    const [tenant, setTenant] = useState<AuthTenant | null>(
        stored.tenantId ? { id: stored.tenantId, name: null } : null,
    );
    const [organizationUnit, setOrganizationUnit] = useState<AuthOrganizationUnit | null>(
        stored.organizationUnitId ? { id: stored.organizationUnitId, name: null } : null,
    );
    const [roles, setRoles] = useState<string[]>([]);
    const [permissions, setPermissions] = useState<string[]>([]);
    const [enabledModules, setEnabledModules] = useState<string[] | null>(null);
    const [isPlatformOperator, setIsPlatformOperator] = useState(false);
    const [isLoading, setIsLoading] = useState<boolean>(Boolean(stored.accessToken));
    const authLoadId = useRef(0);

    const clearAuthState = useCallback(() => {
        authLoadId.current += 1;
        clearStoredAuthSession();
        setToken(null);
        setAuthMode('tenant');
        setSessionId(null);
        setUser(null);
        setTenant(null);
        setOrganizationUnit(null);
        setRoles([]);
        setPermissions([]);
        setEnabledModules(null);
        setIsPlatformOperator(false);
        configureBusinessTimeZone(null);
        setIsLoading(false);
    }, []);

    const applySession = useCallback((next: {
        token?: string | null;
        refresh_token?: string | null;
        session_id?: number | null;
        user: AuthUser;
        tenant: AuthTenant | null;
        organization_unit: AuthOrganizationUnit | null;
        roles?: string[];
        permissions?: string[];
        enabled_modules?: string[] | null;
        is_platform_operator?: boolean;
        auth_mode?: AuthMode;
    }) => {
        const nextAuthMode = next.auth_mode ?? authMode;
        if (next.token) {
            setToken(next.token);
        }
        setAuthMode(nextAuthMode);
        setSessionId(next.session_id ?? null);
        setUser(next.user);
        setTenant(next.tenant);
        setOrganizationUnit(next.organization_unit);
        setRoles(next.roles ?? next.user.roles ?? []);
        setPermissions(next.permissions ?? next.user.permissions ?? []);
        setEnabledModules(next.enabled_modules ?? null);
        setIsPlatformOperator(next.is_platform_operator ?? next.user.is_platform_operator ?? false);
        configureBusinessTimeZone(next.organization_unit?.timezone ?? next.tenant?.timezone);
        storeAuthSession({
            accessToken: next.token ?? token,
            refreshToken: next.refresh_token ?? getStoredApiContext().refreshToken,
            sessionId: next.session_id ?? sessionId,
            tenantId: nextAuthMode === 'tenant' ? toNumber(next.tenant?.id) : null,
            organizationUnitId: nextAuthMode === 'tenant' ? toNumber(next.organization_unit?.id) : null,
            authMode: nextAuthMode,
        });
    }, [authMode, sessionId, token]);

    const loadCurrentUser = useCallback(async (signal?: AbortSignal) => {
        const loadId = authLoadId.current + 1;
        authLoadId.current = loadId;

        setIsLoading(true);
        try {
            if (!getStoredApiContext().accessToken) {
                clearAuthState();
                return;
            }

            const storedContext = getStoredApiContext();
            const current = await authApi.me(storedContext.authMode, signal);
            if (signal?.aborted) return;
            applySession({
                user: current.user,
                tenant: current.tenant,
                organization_unit: current.organization_unit,
                roles: current.roles,
                permissions: current.permissions,
                enabled_modules: current.enabled_modules,
                is_platform_operator: current.is_platform_operator,
                auth_mode: storedContext.authMode,
            });
        } catch (error) {
            if (signal?.aborted) return;
            if (error instanceof ApiError) {
                clearAuthState();
                return;
            }
            clearAuthState();
            throw error;
        } finally {
            if (authLoadId.current === loadId) {
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
            applySession({ ...session, auth_mode: payload.auth_mode });
            const current = await authApi.me(payload.auth_mode);
            applySession({
                token: session.token,
                refresh_token: session.refresh_token,
                session_id: session.session_id,
                user: current.user,
                tenant: current.tenant,
                organization_unit: current.organization_unit,
                roles: current.roles,
                permissions: current.permissions,
                enabled_modules: current.enabled_modules,
                is_platform_operator: current.is_platform_operator,
                auth_mode: payload.auth_mode,
            });
        } finally {
            setIsLoading(false);
        }
    }, [applySession]);

    const logout = useCallback(async () => {
        const current = getStoredApiContext();
        try {
            if (current.accessToken) {
                await authApi.logout(current.authMode, {
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
        if (!token || user) return;

        const controller = new AbortController();
        void loadCurrentUser(controller.signal).catch(() => undefined);

        return () => controller.abort();
    }, [loadCurrentUser, token, user]);

    const value = useMemo<AuthContextValue>(() => ({
        user,
        token,
        tenant,
        organizationUnit,
        roles,
        permissions,
        enabledModules,
        isPlatformOperator,
        authMode,
        isAuthenticated: Boolean(token && user),
        isLoading,
        login,
        logout,
        loadCurrentUser,
    }), [
        authMode,
        enabledModules,
        isPlatformOperator,
        isLoading,
        loadCurrentUser,
        login,
        logout,
        organizationUnit,
        permissions,
        roles,
        tenant,
        token,
        user,
    ]);

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
