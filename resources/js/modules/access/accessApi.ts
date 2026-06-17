import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface AccessRoleSummary {
    id: number;
    name: string;
    description?: string | null;
}

export interface AccessOrganizationUnitSummary {
    id: number;
    code?: string | null;
    name: string;
    is_default?: boolean;
}

export interface AccessPermission {
    id: number;
    name: string;
    module?: string | null;
    resource?: string | null;
    action?: string | null;
    description?: string | null;
    status?: string | null;
    is_read_only?: boolean;
}

export interface AccessUser {
    id: number;
    row_version?: number;
    name?: string | null;
    first_name: string;
    last_name?: string | null;
    username?: string | null;
    email: string;
    phone?: string | null;
    status: string;
    organization_unit_id?: number | null;
    roles?: AccessRoleSummary[];
    permissions?: AccessPermission[];
    organization_units?: AccessOrganizationUnitSummary[];
    last_login_at?: string | null;
}

export interface AccessRole {
    id: number;
    row_version?: number;
    name: string;
    code?: string | null;
    guard_name?: string | null;
    description?: string | null;
    status?: string | null;
    assigned_users_count?: number;
    permissions_count?: number;
    permissions?: AccessPermission[];
}

export interface UserPayload {
    first_name: string;
    last_name?: string | null;
    username?: string | null;
    email: string;
    phone?: string | null;
    status?: string;
    password?: string;
    role_ids?: number[];
    organization_unit_ids?: number[];
    default_organization_unit_id?: number | null;
    row_version?: number;
}

export interface RolePayload {
    name: string;
    guard_name?: string | null;
    description?: string | null;
    permission_ids?: number[];
    row_version?: number;
}

export const accessApi = {
    async listUsers(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessUser>> {
        const response = await apiClient.get<ApiCollection<AccessUser>>(endpoints.users, { params, signal });
        return response.data;
    },

    async getUser(id: number | string, signal?: AbortSignal): Promise<AccessUser> {
        const response = await apiClient.get<ApiResource<AccessUser>>(`${endpoints.users}/${id}`, { signal });
        return response.data.data;
    },

    async createUser(payload: UserPayload): Promise<AccessUser> {
        const response = await apiClient.post<ApiResource<AccessUser>>(endpoints.users, payload);
        return response.data.data;
    },

    async updateUser(id: number | string, payload: UserPayload): Promise<AccessUser> {
        const response = await apiClient.put<ApiResource<AccessUser>>(`${endpoints.users}/${id}`, payload);
        return response.data.data;
    },

    async activateUser(id: number | string): Promise<AccessUser> {
        const response = await apiClient.patch<ApiResource<AccessUser>>(`${endpoints.users}/${id}/activate`);
        return response.data.data;
    },

    async deactivateUser(id: number | string): Promise<AccessUser> {
        const response = await apiClient.patch<ApiResource<AccessUser>>(`${endpoints.users}/${id}/deactivate`);
        return response.data.data;
    },

    async listRoles(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessRole>> {
        const response = await apiClient.get<ApiCollection<AccessRole>>(endpoints.roles, { params, signal });
        return response.data;
    },

    async getRole(id: number | string, signal?: AbortSignal): Promise<AccessRole> {
        const response = await apiClient.get<ApiResource<AccessRole>>(`${endpoints.roles}/${id}`, { signal });
        return response.data.data;
    },

    async createRole(payload: RolePayload): Promise<AccessRole> {
        const response = await apiClient.post<ApiResource<AccessRole>>(endpoints.roles, payload);
        return response.data.data;
    },

    async updateRole(id: number | string, payload: RolePayload): Promise<AccessRole> {
        const response = await apiClient.put<ApiResource<AccessRole>>(`${endpoints.roles}/${id}`, payload);
        return response.data.data;
    },

    async deleteRole(id: number | string): Promise<void> {
        await apiClient.delete(`${endpoints.roles}/${id}`);
    },

    async listPermissions(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessPermission>> {
        const response = await apiClient.get<ApiCollection<AccessPermission>>(endpoints.permissions, { params, signal });
        return response.data;
    },

    async listOrganizationUnits(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessOrganizationUnitSummary>> {
        const response = await apiClient.get<ApiCollection<AccessOrganizationUnitSummary>>(endpoints.organizationUnits, { params, signal });
        return response.data;
    },
};
