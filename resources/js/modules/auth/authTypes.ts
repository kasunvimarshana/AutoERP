export interface AuthUser {
    id: number | string;
    name: string | null;
    email: string | null;
    roles?: string[];
    permissions?: string[];
}

export interface AuthTenant {
    id: number | string;
    name: string | null;
}

export interface AuthOrganizationUnit {
    id: number | string;
    name: string | null;
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
}

export interface CurrentUserResponse {
    user: AuthUser;
    tenant: AuthTenant | null;
    organization_unit: AuthOrganizationUnit | null;
    roles?: string[];
    permissions?: string[];
}

export interface LoginPayload {
    login_identifier: string;
    password: string;
    tenant_id?: number | null;
    organization_unit_id?: number | null;
    device_name?: string | null;
}
