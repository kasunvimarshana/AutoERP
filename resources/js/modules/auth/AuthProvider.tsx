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
import { ApiError, toApiError } from '@/shared/api/apiError';
import { refreshAccessToken } from '@/shared/api/authRefreshCoordinator';
import {
    AUTH_SESSION_INVALIDATED_EVENT,
    AUTH_SESSION_MARKER_KEY,
    clearStoredAuthSession,
    commitAuthSession,
    getStoredApiContext,
    setTransientAccessToken,
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
    permissionsLoaded: boolean;
    enabledModules: string[] | null;
    enabledModulesLoaded: boolean;
    isPlatformOperator: boolean;
    authMode: AuthMode;
    isAuthenticated: boolean;
    isLoading: boolean;
    bootstrapError: ApiError | null;
    login: (payload: LoginPayload) => Promise<void>;
    logout: () => Promise<void>;
    loadCurrentUser: (signal?: AbortSignal) => Promise<void>;
    switchOrganizationUnit: (organizationUnitId: number) => Promise<void>;
}

interface SessionPayload {
    token: string;
    sessionId: number | null;
    user: AuthUser;
    tenant: AuthTenant | null;
    organizationUnit: AuthOrganizationUnit | null;
    roles: string[];
    permissions: string[];
    enabledModules: string[] | null;
    isPlatformOperator: boolean;
    authMode: AuthMode;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const initial = getStoredApiContext();
    const [token, setToken] = useState<string | null>(initial.accessToken);
    const [authMode, setAuthMode] = useState<AuthMode>(initial.authMode);
    const [user, setUser] = useState<AuthUser | null>(null);
    const [tenant, setTenant] = useState<AuthTenant | null>(null);
    const [organizationUnit, setOrganizationUnit] = useState<AuthOrganizationUnit | null>(null);
    const [roles, setRoles] = useState<string[]>([]);
    const [permissions, setPermissions] = useState<string[]>([]);
    const [permissionsLoaded, setPermissionsLoaded] = useState(false);
    const [enabledModules, setEnabledModules] = useState<string[] | null>(null);
    const [enabledModulesLoaded, setEnabledModulesLoaded] = useState(false);
    const [isPlatformOperator, setIsPlatformOperator] = useState(false);
    const [isLoading, setIsLoading] = useState(initial.hasSession);
    const [bootstrapError, setBootstrapError] = useState<ApiError | null>(null);
    const authLoadId = useRef(0);

    const resetReactState = useCallback(() => {
        authLoadId.current += 1;
        setToken(null);
        setAuthMode('tenant');
        setUser(null);
        setTenant(null);
        setOrganizationUnit(null);
        setRoles([]);
        setPermissions([]);
        setPermissionsLoaded(false);
        setEnabledModules(null);
        setEnabledModulesLoaded(false);
        setIsPlatformOperator(false);
        setBootstrapError(null);
        configureBusinessTimeZone(null);
        setIsLoading(false);
    }, []);

    const clearAuthState = useCallback((clearStorage = true) => {
        if (clearStorage) clearStoredAuthSession();
        resetReactState();
    }, [resetReactState]);

    const commitSession = useCallback((session: SessionPayload) => {
        const tenantId = session.authMode === 'tenant' ? toPositiveInteger(session.tenant?.id) : null;
        commitAuthSession({
            accessToken: session.token,
            sessionId: session.sessionId,
            tenantId,
            authMode: session.authMode,
        });
        setToken(session.token);
        setAuthMode(session.authMode);
        setUser(session.user);
        setTenant(session.tenant);
        setOrganizationUnit(session.organizationUnit);
        setRoles(session.roles);
        setPermissions(session.permissions);
        setPermissionsLoaded(true);
        setEnabledModules(session.enabledModules);
        setEnabledModulesLoaded(true);
        setIsPlatformOperator(session.isPlatformOperator);
        setBootstrapError(null);
        configureBusinessTimeZone(session.organizationUnit?.timezone ?? session.tenant?.timezone);
    }, []);

    const loadCurrentUser = useCallback(async (signal?: AbortSignal) => {
        const loadId = authLoadId.current + 1;
        authLoadId.current = loadId;
        setIsLoading(true);
        setBootstrapError(null);

        try {
            let storedContext = getStoredApiContext();
            if (!storedContext.hasSession) {
                clearAuthState(false);
                return;
            }

            let accessToken = storedContext.accessToken;
            if (!accessToken) {
                accessToken = await refreshAccessToken();
                if (signal?.aborted) return;
                setToken(accessToken);
                storedContext = getStoredApiContext();
            }

            const current = await authApi.me(storedContext.authMode, signal);
            if (signal?.aborted) return;

            commitSession({
                token: accessToken,
                sessionId: storedContext.sessionId,
                user: current.user,
                tenant: current.tenant,
                organizationUnit: current.organization_unit,
                roles: current.roles ?? current.user.roles ?? [],
                permissions: current.permissions ?? current.user.permissions ?? [],
                enabledModules: current.enabled_modules ?? null,
                isPlatformOperator: current.is_platform_operator ?? current.user.is_platform_operator ?? false,
                authMode: storedContext.authMode,
            });
        } catch (error: unknown) {
            if (signal?.aborted) return;
            const apiError = toApiError(error);
            if (isDefinitiveSessionFailure(apiError)) {
                clearAuthState();
                return;
            }

            setBootstrapError(apiError);
        } finally {
            if (authLoadId.current === loadId) {
                setIsLoading(false);
            }
        }
    }, [clearAuthState, commitSession]);

    const login = useCallback(async (payload: LoginPayload) => {
        setIsLoading(true);
        setBootstrapError(null);
        let issuedToken: string | null = null;

        try {
            const session = await authApi.login({
                ...payload,
                device_name: payload.device_name ?? window.navigator.userAgent.slice(0, 160),
            });
            issuedToken = session.token.trim();
            if (issuedToken === '') {
                throw new ApiError('Login response did not include an access token.', 502, 'INVALID_LOGIN_RESPONSE', 'infrastructure');
            }

            setTransientAccessToken(issuedToken);
            const current = await authApi.me(payload.auth_mode);
            commitSession({
                token: issuedToken,
                sessionId: toPositiveInteger(session.session_id),
                user: current.user,
                tenant: current.tenant,
                organizationUnit: current.organization_unit,
                roles: current.roles ?? current.user.roles ?? [],
                permissions: current.permissions ?? current.user.permissions ?? [],
                enabledModules: current.enabled_modules ?? null,
                isPlatformOperator: current.is_platform_operator ?? current.user.is_platform_operator ?? false,
                authMode: payload.auth_mode,
            });
        } catch (error: unknown) {
            if (issuedToken) {
                try {
                    await authApi.logout(payload.auth_mode, { access_token: issuedToken });
                } catch {
                    // The local transaction still rolls back; the access token expires server-side.
                }
            }
            clearAuthState();
            throw toApiError(error);
        } finally {
            setIsLoading(false);
        }
    }, [clearAuthState, commitSession]);

    const switchOrganizationUnit = useCallback(async (organizationUnitId: number) => {
        if (!Number.isSafeInteger(organizationUnitId) || organizationUnitId < 1) {
            throw new ApiError('Select a valid organization unit.', 422, 'INVALID_ORGANIZATION_UNIT', 'validation');
        }

        await authApi.switchOrganizationUnit(organizationUnitId);
        await loadCurrentUser();
    }, [loadCurrentUser]);

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
        function handleInvalidatedSession() {
            clearAuthState(false);
        }

        function handleStorage(event: StorageEvent) {
            if (event.storageArea !== window.localStorage || event.key !== AUTH_SESSION_MARKER_KEY) return;
            if (event.newValue === null) {
                clearAuthState(false);
                return;
            }

            resetReactState();
            setIsLoading(true);
            void loadCurrentUser();
        }

        window.addEventListener(AUTH_SESSION_INVALIDATED_EVENT, handleInvalidatedSession);
        window.addEventListener('storage', handleStorage);
        return () => {
            window.removeEventListener(AUTH_SESSION_INVALIDATED_EVENT, handleInvalidatedSession);
            window.removeEventListener('storage', handleStorage);
        };
    }, [clearAuthState, loadCurrentUser, resetReactState]);

    useEffect(() => {
        if (!getStoredApiContext().hasSession || user || bootstrapError) return;

        const controller = new AbortController();
        void Promise.resolve().then(() => loadCurrentUser(controller.signal));

        return () => controller.abort();
    }, [bootstrapError, loadCurrentUser, user]);

    const value = useMemo<AuthContextValue>(() => ({
        user,
        token,
        tenant,
        organizationUnit,
        roles,
        permissions,
        permissionsLoaded,
        enabledModules,
        enabledModulesLoaded,
        isPlatformOperator,
        authMode,
        isAuthenticated: Boolean(token && user),
        isLoading,
        bootstrapError,
        login,
        logout,
        loadCurrentUser,
        switchOrganizationUnit,
    }), [
        authMode,
        bootstrapError,
        enabledModules,
        enabledModulesLoaded,
        isPlatformOperator,
        isLoading,
        loadCurrentUser,
        login,
        logout,
        organizationUnit,
        permissions,
        permissionsLoaded,
        roles,
        switchOrganizationUnit,
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

function isDefinitiveSessionFailure(error: ApiError): boolean {
    return error.status === 401
        || error.code === 'TOKEN_INVALID'
        || error.code === 'TOKEN_REVOKED'
        || error.code === 'AUTH_SESSION_MISSING';
}

function toPositiveInteger(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined || value === '') return null;

    const numeric = Number(value);
    return Number.isSafeInteger(numeric) && numeric > 0 ? numeric : null;
}
