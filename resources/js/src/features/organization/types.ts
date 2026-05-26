import type { UserRecord } from '../access/types';

export type OrganizationUnitRecord = {
    id: number;
    tenant_id: number;
    type_id: number | null;
    parent_id: number | null;
    manager_user_id: number | null;
    name: string;
    code: string | null;
    path: string | null;
    depth: number;
    metadata: Record<string, unknown> | null;
    is_active: boolean;
    description: string | null;
    avatar_url: string | null;
    attachments?: Array<Record<string, unknown>>;
    users?: UserRecord[];
    created_at: string;
    updated_at: string;
};

export type OrganizationUnitTypeRecord = {
    id: number;
    tenant_id: number;
    name: string;
    level: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type OrganizationUnitUserAssignment = {
    id: number;
    tenant_id: number;
    org_unit_id: number;
    user_id: number;
    role: string | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
};

export type OrganizationUnitListFilters = {
    tenant_id?: number;
    type_id?: number;
    parent_id?: number;
    manager_user_id?: number;
    name?: string;
    code?: string;
    is_active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
    include?: string;
};

export type OrganizationUnitTypeListFilters = {
    tenant_id?: number;
    name?: string;
    level?: number;
    is_active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type OrganizationUnitPayload = {
    tenant_id?: number;
    type_id?: number | null;
    parent_id?: number | null;
    manager_user_id?: number | null;
    name: string;
    code?: string | null;
    metadata?: Record<string, unknown> | null;
    is_active: boolean;
    description?: string | null;
};

export type OrganizationUnitTypePayload = {
    tenant_id?: number;
    name: string;
    level: number;
    is_active: boolean;
};

export type OrganizationUnitUserPayload = {
    tenant_id?: number;
    org_unit_id?: number;
    user_id: number;
    role?: string | null;
    is_primary: boolean;
};
