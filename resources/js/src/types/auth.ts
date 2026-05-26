export type AuthToken = {
    access_token: string;
    token_type: string;
    expires_in: number;
    refresh_token: string | null;
    scopes: string[];
};

export type AuthUser = {
    id: number;
    tenant_id: number | null;
    email: string | null;
    first_name: string | null;
    last_name: string | null;
    status: string | null;
    roles: string[];
    permissions: string[];
};

export type LoginPayload = {
    tenant_id?: number;
    email: string;
    password: string;
};

export type RegisterPayload = {
    tenant_id: number;
    email: string;
    first_name: string;
    last_name: string;
    password: string;
    password_confirmation: string;
    phone?: string | null;
};
