import type { ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { getStoredAccessToken, getStoredAuthSession } from '../../../services/api/authTokenStorage';
import type { AuthSession, AuthUser, LoginInput } from '../types/auth.types';

type BackendRecord = Record<string, unknown>;

function isRecord(value: unknown): value is BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function asRecord(value: unknown): BackendRecord {
    return isRecord(value) ? value : {};
}

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const parsed = asString(value).trim();

    return parsed === '' ? undefined : parsed;
}

function asNumericString(value: string | undefined): number | undefined {
    if (value === undefined || value.trim() === '') {
        return undefined;
    }

    const parsed = Number(value);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function asStringList(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((entry) => {
            if (typeof entry === 'string') {
                return entry;
            }

            if (isRecord(entry)) {
                return asString(entry.name ?? entry.code ?? entry.key);
            }

            return '';
        })
        .filter((entry) => entry.trim() !== '');
}

function authShapeError(message = 'Unexpected authentication response from server.'): ApiError {
    return new ApiError(message, 502, {}, 'AUTH_UNEXPECTED_RESPONSE', 'response');
}

function normalizeUser(raw: BackendRecord, context: BackendRecord = {}): AuthUser {
    const metadata = asRecord(raw.metadata);
    const firstName = asOptionalString(raw.first_name ?? raw.firstName);
    const lastName = asOptionalString(raw.last_name ?? raw.lastName);
    const displayName = asOptionalString(raw.display_name ?? raw.name);
    const fallbackName = [firstName, lastName].filter(Boolean).join(' ').trim();
    const roles = asStringList(raw.roles ?? metadata.roles);
    const permissions = asStringList(raw.permissions ?? metadata.permissions);
    const id = asString(raw.id ?? raw.user_id ?? context.user_id);

    if (id === '') {
        throw authShapeError('Authenticated user id was missing from the response.');
    }

    return {
        email: asString(raw.email),
        firstName,
        id,
        lastName,
        name: (displayName ?? fallbackName) || `User ${id}`,
        organizationUnitId: asOptionalString(raw.organization_unit_id ?? raw.organizationUnitId ?? context.organization_unit_id),
        permissions,
        role: asString(raw.role ?? metadata.role ?? roles[0], 'Authenticated User'),
        roles,
        status: asString(raw.status, 'active'),
        tenantId: asOptionalString(raw.tenant_id ?? raw.tenantId ?? context.tenant_id),
    };
}

function normalizeSession(raw: BackendRecord): AuthSession {
    const tokens = asRecord(raw.tokens);
    const session = asRecord(raw.session);
    const user = normalizeUser(asRecord(raw.user), raw);
    const accessToken = asString(tokens.access_token);

    if (accessToken === '') {
        throw authShapeError('Access token was missing from the login response.');
    }

    return {
        accessToken,
        accessTokenExpiresAt: asOptionalString(tokens.access_token_expires_at),
        organizationUnitId: asOptionalString(session.organization_unit_id ?? user.organizationUnitId ?? raw.organization_unit_id),
        refreshToken: asOptionalString(tokens.refresh_token),
        refreshTokenExpiresAt: asOptionalString(tokens.refresh_token_expires_at),
        sessionId: asOptionalString(session.id ?? raw.session_id),
        tenantId: asOptionalString(session.tenant_id ?? user.tenantId ?? raw.tenant_id),
        tokenType: asString(tokens.token_type, 'Bearer'),
        user,
    };
}

function userFromContext(context: BackendRecord): AuthUser {
    const stored = getStoredAuthSession().user;
    const contextUser = asRecord(context.user);
    const userId = asString(context.user_id ?? stored?.id);
    const contextRoles = asStringList(context.roles);
    const contextPermissions = asStringList(context.permissions);

    if (Object.keys(contextUser).length > 0) {
        return normalizeUser(
            {
                ...contextUser,
                permissions: contextUser.permissions ?? context.permissions,
                roles: contextUser.roles ?? context.roles,
            },
            context,
        );
    }

    if (stored && stored.id === userId) {
        return {
            ...stored,
            organizationUnitId: asOptionalString(context.organization_unit_id) ?? stored.organizationUnitId,
            permissions: contextPermissions.length > 0 ? contextPermissions : stored.permissions,
            roles: contextRoles.length > 0 ? contextRoles : stored.roles,
            tenantId: asOptionalString(context.tenant_id) ?? stored.tenantId,
        };
    }

    return normalizeUser({ id: userId, permissions: contextPermissions, roles: contextRoles }, context);
}

export const authApi = {
    getCurrentUser: async (): Promise<AuthUser> => {
        if (!getStoredAccessToken()) {
            throw new ApiError('No stored authentication session exists.', 401, {}, 'AUTH_SESSION_MISSING', 'authentication');
        }

        const response = await httpClient<ApiResponse<BackendRecord>>('/api/auth/me');

        return userFromContext(response.data);
    },
    login: async (input: LoginInput): Promise<AuthSession> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/auth/login', {
            body: {
                device_name: 'Web browser',
                login_identifier: input.loginIdentifier,
                organization_unit_id: asNumericString(input.organizationUnitId),
                password: input.password,
                provider_key: 'internal',
                tenant_id: asNumericString(input.tenantId),
                user_agent: navigator.userAgent,
            },
            method: 'POST',
        });

        return normalizeSession(response.data);
    },
    logout: async (): Promise<void> => {
        const stored = getStoredAuthSession();

        if (!stored.accessToken) {
            return;
        }

        await httpClient<ApiResponse<boolean> | undefined>('/api/auth/logout', {
            body: {
                access_token: stored.accessToken,
                session_id: stored.sessionId ? Number(stored.sessionId) : undefined,
                tenant_id: stored.tenantId ? Number(stored.tenantId) : undefined,
            },
            method: 'POST',
        });
    },
};
