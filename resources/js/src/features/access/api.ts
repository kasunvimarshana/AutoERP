import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    ChangePasswordPayload,
    PermissionListFilters,
    PermissionRecord,
    ProfilePayload,
    RoleListFilters,
    RolePayload,
    RoleRecord,
    SyncRolePermissionsPayload,
    UserListFilters,
    UserPayload,
    UserRecord,
} from './types';

export const accessApi = {
    listUsers(filters: UserListFilters): Promise<PaginatedResult<UserRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<UserRecord>>('/users', { query: toQuery(filters) }).then((payload) => unwrapPaginated<UserRecord>(payload));
    },
    getUser(userId: number, include?: string) {
        return apiClient
            .get<ApiResourceEnvelope<UserRecord> | UserRecord>(`/users/${userId}`, { query: include ? { include } : undefined })
            .then((payload) => unwrapResource<UserRecord>(payload));
    },
    createUser(payload: UserPayload) {
        return apiClient.post<ApiResourceEnvelope<UserRecord> | UserRecord>('/users', payload).then((result) => unwrapResource<UserRecord>(result));
    },
    updateUser(userId: number, payload: Partial<UserPayload>) {
        return apiClient.put<ApiResourceEnvelope<UserRecord> | UserRecord>(`/users/${userId}`, payload).then((result) => unwrapResource<UserRecord>(result));
    },
    deleteUser(userId: number) {
        return apiClient.delete<{ message: string }>(`/users/${userId}`);
    },
    listRoles(filters: RoleListFilters): Promise<PaginatedResult<RoleRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<RoleRecord>>('/roles', { query: toQuery(filters) }).then((payload) => unwrapPaginated<RoleRecord>(payload));
    },
    getRole(roleId: number) {
        return apiClient.get<ApiResourceEnvelope<RoleRecord> | RoleRecord>(`/roles/${roleId}`).then((payload) => unwrapResource<RoleRecord>(payload));
    },
    createRole(payload: RolePayload) {
        return apiClient.post<ApiResourceEnvelope<RoleRecord> | RoleRecord>('/roles', payload).then((result) => unwrapResource<RoleRecord>(result));
    },
    syncRolePermissions(roleId: number, payload: SyncRolePermissionsPayload) {
        return apiClient
            .put<ApiResourceEnvelope<RoleRecord> | RoleRecord>(`/roles/${roleId}/permissions`, payload)
            .then((result) => unwrapResource<RoleRecord>(result));
    },
    deleteRole(roleId: number) {
        return apiClient.delete<{ message: string }>(`/roles/${roleId}`);
    },
    listPermissions(filters: PermissionListFilters): Promise<PaginatedResult<PermissionRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<PermissionRecord>>('/permissions', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<PermissionRecord>(payload));
    },
    getProfile() {
        return apiClient.get<ApiResourceEnvelope<UserRecord> | UserRecord>('/profile').then((payload) => unwrapResource<UserRecord>(payload));
    },
    updateProfile(payload: ProfilePayload) {
        return apiClient.patch<ApiResourceEnvelope<UserRecord> | UserRecord>('/profile', payload).then((result) => unwrapResource<UserRecord>(result));
    },
    changePassword(payload: ChangePasswordPayload) {
        return apiClient.post<{ message: string }>('/profile/change-password', payload);
    },
};
