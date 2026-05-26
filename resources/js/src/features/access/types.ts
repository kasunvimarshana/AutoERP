export type RolePermission = {
    id: number;
    tenant_id: number;
    name: string;
};

export type UserRole = {
    id: number;
    tenant_id: number;
    name: string;
    permissions: RolePermission[];
};

export type UserRecord = {
    id: number;
    tenant_id: number;
    org_unit_id: number | null;
    email: string | null;
    first_name: string | null;
    last_name: string | null;
    full_name: string | null;
    phone: string | null;
    avatar: string | null;
    address: Record<string, unknown> | null;
    preferences: Record<string, unknown> | null;
    active: boolean;
    roles: UserRole[];
    permissions?: RolePermission[];
    created_at: string;
    updated_at: string;
};

export type RoleRecord = {
    id: number;
    tenant_id: number;
    name: string;
    permissions: RolePermission[];
};

export type PermissionRecord = RolePermission;

export type UserListFilters = {
    tenant_id?: number;
    org_unit_id?: number;
    email?: string;
    first_name?: string;
    last_name?: string;
    active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
    include?: string;
};

export type RoleListFilters = {
    tenant_id?: number;
    per_page?: number;
    page?: number;
};

export type PermissionListFilters = {
    tenant_id?: number;
    per_page?: number;
    page?: number;
};

export type UserPayload = {
    tenant_id?: number;
    org_unit_id?: number | null;
    email: string;
    first_name: string;
    last_name: string;
    phone?: string | null;
    active: boolean;
    address?: Record<string, unknown> | null;
    preferences?: Record<string, unknown> | null;
    avatar?: string | null;
    roles?: number[];
};

export type RolePayload = {
    tenant_id: number;
    name: string;
};

export type SyncRolePermissionsPayload = {
    permission_ids: number[];
};

export type ProfilePayload = {
    first_name: string;
    last_name: string;
    phone?: string | null;
    address?: Record<string, unknown> | null;
};

export type ChangePasswordPayload = {
    current_password: string;
    password: string;
    password_confirmation: string;
};
