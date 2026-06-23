import type { AuthMode } from '@/shared/api/authSessionStorage';

export interface AuthUser {
    id: number | string;
    name: string | null;
    email: string | null;
    roles?: string[];
    permissions?: string[];
    is_platform_operator?: boolean;
}

export interface AuthTenant {
    id: number | string;
    name: string | null;
    timezone?: string | null;
}

export interface AuthOrganizationUnit {
    id: number | string;
    name: string | null;
    timezone?: string | null;
}

export interface AuthSession {
    token: string;
    refresh_token?: string | null;
    token_type: 'Bearer' | string;
    session_id?: number | null;
    user: AuthUser;
    tenant: AuthTenant | null;
    organization_unit: AuthOrganizationUnit | null;
    roles?: string[];
    permissions?: string[];
    enabled_modules?: string[] | null;
    is_platform_operator?: boolean;
}

export interface CurrentUserResponse {
    user: AuthUser;
    tenant: AuthTenant | null;
    organization_unit: AuthOrganizationUnit | null;
    roles?: string[];
    permissions?: string[];
    enabled_modules?: string[] | null;
    is_platform_operator?: boolean;
}

export interface LoginPayload {
    auth_mode: AuthMode;
    login_identifier: string;
    password: string;
    tenant_code?: string | null;
    organization_unit_id?: number | null;
    device_name?: string | null;
}
