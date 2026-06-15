import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export type AccessEntityKind = 'users' | 'roles' | 'permissions';

export interface AccessRecord extends Record<string, unknown> {
    id: number;
    first_name?: string;
    last_name?: string | null;
    username?: string | null;
    email?: string;
    phone?: string | null;
    status?: string;
    name?: string;
    guard_name?: string;
    module?: string | null;
    description?: string | null;
}

export interface CreateAccessRecordPayload extends Record<string, unknown> {
    tenant_id: number;
}

const accessEndpoints = {
    users: endpoints.users,
    roles: endpoints.roles,
    permissions: endpoints.permissions,
} satisfies Record<AccessEntityKind, string>;

export async function listAccessRecords(
    kind: AccessEntityKind,
    params: ListParams,
    signal?: AbortSignal,
): Promise<ApiCollection<AccessRecord>> {
    const response = await apiClient.get<ApiCollection<AccessRecord>>(accessEndpoints[kind], { params, signal });
    return response.data;
}

export async function createAccessRecord(
    kind: AccessEntityKind,
    payload: CreateAccessRecordPayload,
): Promise<AccessRecord> {
    const response = await apiClient.post<ApiResource<AccessRecord>>(accessEndpoints[kind], payload);
    return response.data.data;
}
