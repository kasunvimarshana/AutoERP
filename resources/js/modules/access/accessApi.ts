import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface AccessRoleSummary {
    id: number;
    name: string;
    description?: string | null;
    system_key?: string | null;
}

export interface AccessOrganizationUnitSummary {
    id: number;
    code?: string | null;
    name: string;
    path?: string | null;
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
    row_version: number;
    name?: string | null;
    first_name: string;
    last_name?: string | null;
    username?: string | null;
    email: string;
    phone?: string | null;
    status: string;
    credentials_ready?: boolean;
    invited_at?: string | null;
    activated_at?: string | null;
    roles?: AccessRoleSummary[];
    direct_permissions?: AccessPermission[];
    permissions?: AccessPermission[];
    organization_units?: AccessOrganizationUnitSummary[];
    default_organization_unit_id?: number | null;
    last_login_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
}


export interface AccessUserDocument {
    id: number;
    row_version: number;
    user_id: number;
    name: string;
    document_type: string;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    checksum_sha256: string;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface AccessUserDevice {
    id: number;
    row_version: number;
    user_id: number;
    platform: string;
    device_name?: string | null;
    last_active_at?: string | null;
    revoked_at?: string | null;
    created_at?: string | null;
}

export interface AccessMutationResult {
    id: number;
    row_version: number;
}

export interface PasswordPolicyRequirements {
    minimum_length: number;
    mixed_case: boolean;
    numbers: boolean;
    symbols: boolean;
}

export interface AccessRole {
    id: number;
    row_version: number;
    name: string;
    guard_name?: string | null;
    description?: string | null;
    system_key?: string | null;
    is_system?: boolean;
    assigned_users_count?: number;
    permissions_count?: number;
    permissions?: AccessPermission[];
}

export interface CreateUserPayload {
    first_name: string;
    last_name?: string | null;
    username?: string | null;
    email: string;
    phone?: string | null;
    role_ids: number[];
    organization_unit_ids: number[];
    default_organization_unit_id: number;
    password: string;
    password_confirmation: string;
}

export interface UpdateUserProfilePayload {
    expected_version: number;
    first_name: string;
    last_name?: string | null;
    username?: string | null;
    phone?: string | null;
}

export interface RolePayload {
    name: string;
    description?: string | null;
    expected_version?: number;
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

    async createUser(payload: CreateUserPayload): Promise<AccessUser> {
        const response = await apiClient.post<ApiResource<AccessUser>>(endpoints.users, payload);
        return response.data.data;
    },

    async getUserPasswordPolicy(signal?: AbortSignal): Promise<PasswordPolicyRequirements> {
        const response = await apiClient.get<ApiResource<PasswordPolicyRequirements>>(`${endpoints.users}/password-policy`, { signal });
        return response.data.data;
    },

    async updateUserProfile(id: number | string, payload: UpdateUserProfilePayload): Promise<AccessUser> {
        const response = await apiClient.patch<ApiResource<AccessUser>>(`${endpoints.users}/${id}`, payload);
        return response.data.data;
    },

    async syncUserRoles(id: number | string, expectedVersion: number, roleIds: number[]): Promise<AccessMutationResult> {
        const response = await apiClient.put<ApiResource<AccessMutationResult>>(`${endpoints.users}/${id}/roles`, {
            expected_version: expectedVersion,
            role_ids: roleIds,
        });
        return response.data.data;
    },

    async syncUserPermissions(id: number | string, expectedVersion: number, permissionIds: number[]): Promise<AccessMutationResult> {
        const response = await apiClient.put<ApiResource<AccessMutationResult>>(`${endpoints.users}/${id}/permissions`, {
            expected_version: expectedVersion,
            permission_ids: permissionIds,
        });
        return response.data.data;
    },

    async syncUserOrganizationAccess(
        id: number | string,
        expectedVersion: number,
        organizationUnitIds: number[],
        defaultOrganizationUnitId: number,
    ): Promise<AccessMutationResult> {
        const response = await apiClient.put<ApiResource<AccessMutationResult>>(`${endpoints.users}/${id}/organization-access`, {
            expected_version: expectedVersion,
            organization_unit_ids: organizationUnitIds,
            default_organization_unit_id: defaultOrganizationUnitId,
        });
        return response.data.data;
    },

    async changeUserStatus(id: number | string, expectedVersion: number, status: string, reason: string): Promise<AccessUser> {
        const response = await apiClient.patch<ApiResource<AccessUser>>(`${endpoints.users}/${id}/status`, {
            expected_version: expectedVersion,
            status,
            reason,
        });
        return response.data.data;
    },

    async archiveUser(id: number | string, expectedVersion: number, reason: string): Promise<void> {
        await apiClient.delete(`${endpoints.users}/${id}`, {
            data: { expected_version: expectedVersion, reason },
        });
    },


    async listUserDocuments(
        userId: number | string,
        params: ListParams,
        signal?: AbortSignal,
    ): Promise<ApiCollection<AccessUserDocument>> {
        const response = await apiClient.get<ApiCollection<AccessUserDocument>>(`${endpoints.users}/${userId}/documents`, { params, signal });
        return response.data;
    },

    async createUserDocument(userId: number | string, payload: FormData): Promise<AccessUserDocument> {
        const response = await apiClient.post<ApiResource<AccessUserDocument>>(`${endpoints.users}/${userId}/documents`, payload);
        return response.data.data;
    },

    async updateUserDocument(
        userId: number | string,
        documentId: number | string,
        source: FormData,
    ): Promise<AccessUserDocument> {
        const payload = new FormData();
        source.forEach((value, key) => payload.append(key, value));
        payload.append('_method', 'PATCH');
        const response = await apiClient.post<ApiResource<AccessUserDocument>>(
            `${endpoints.users}/${userId}/documents/${documentId}`,
            payload,
        );
        return response.data.data;
    },

    async deleteUserDocument(
        userId: number | string,
        documentId: number | string,
        expectedVersion: number,
    ): Promise<void> {
        await apiClient.delete(`${endpoints.users}/${userId}/documents/${documentId}`, {
            data: { expected_version: expectedVersion },
        });
    },

    async downloadUserDocument(userId: number | string, document: AccessUserDocument): Promise<void> {
        const response = await apiClient.get<Blob>(
            `${endpoints.users}/${userId}/documents/${document.id}/download`,
            { responseType: 'blob' },
        );
        const url = URL.createObjectURL(response.data);
        const link = window.document.createElement('a');
        link.href = url;
        link.download = document.original_filename;
        window.document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
    },

    async listUserDevices(
        userId: number | string,
        params: ListParams,
        signal?: AbortSignal,
    ): Promise<ApiCollection<AccessUserDevice>> {
        const response = await apiClient.get<ApiCollection<AccessUserDevice>>(`${endpoints.users}/${userId}/devices`, { params, signal });
        return response.data;
    },

    async revokeUserDevice(
        userId: number | string,
        deviceId: number | string,
        expectedVersion: number,
    ): Promise<AccessUserDevice> {
        const response = await apiClient.post<ApiResource<AccessUserDevice>>(
            `${endpoints.users}/${userId}/devices/${deviceId}/revoke`,
            { expected_version: expectedVersion },
        );
        return response.data.data;
    },

    async listRoles(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessRole>> {
        const response = await apiClient.get<ApiCollection<AccessRole>>(endpoints.roles, { params, signal });
        return response.data;
    },

    async listAllRoles(signal?: AbortSignal): Promise<AccessRole[]> {
        return collectAllPages((page) => accessApi.listRoles({ page, per_page: 100 }, signal), signal);
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
        const response = await apiClient.patch<ApiResource<AccessRole>>(`${endpoints.roles}/${id}`, payload);
        return response.data.data;
    },

    async syncRolePermissions(id: number | string, expectedVersion: number, permissionIds: number[]): Promise<AccessRole> {
        const response = await apiClient.put<ApiResource<AccessRole>>(`${endpoints.roles}/${id}/permissions`, {
            expected_version: expectedVersion,
            permission_ids: permissionIds,
        });
        return response.data.data;
    },

    async deleteRole(id: number | string, expectedVersion: number): Promise<void> {
        await apiClient.delete(`${endpoints.roles}/${id}`, { data: { expected_version: expectedVersion } });
    },

    async listPermissions(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessPermission>> {
        const response = await apiClient.get<ApiCollection<AccessPermission>>(endpoints.permissions, { params, signal });
        return response.data;
    },

    async listAllPermissions(signal?: AbortSignal): Promise<AccessPermission[]> {
        return collectAllPages((page) => accessApi.listPermissions({ page, per_page: 100 }, signal), signal);
    },

    async listPermissionModules(signal?: AbortSignal): Promise<string[]> {
        const response = await apiClient.get<ApiResource<string[]>>(`${endpoints.permissions}/modules`, { signal });
        return response.data.data;
    },

    async listOrganizationUnits(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<AccessOrganizationUnitSummary>> {
        const response = await apiClient.get<ApiCollection<AccessOrganizationUnitSummary>>(endpoints.organizationUnits, { params, signal });
        return response.data;
    },

    async listAllOrganizationUnits(signal?: AbortSignal): Promise<AccessOrganizationUnitSummary[]> {
        return collectAllPages(
            (page) => accessApi.listOrganizationUnits({ page, per_page: 100 }, signal),
            signal,
        );
    },
};

async function collectAllPages<T>(
    loadPage: (page: number) => Promise<ApiCollection<T>>,
    signal?: AbortSignal,
): Promise<T[]> {
    const items: T[] = [];
    let page = 1;
    let lastPage = 1;

    do {
        if (signal?.aborted) throw new DOMException('The request was aborted.', 'AbortError');
        const response = await loadPage(page);
        items.push(...response.data);
        lastPage = Math.max(1, response.meta?.last_page ?? 1);
        page += 1;
    } while (page <= lastPage);

    return items;
}
