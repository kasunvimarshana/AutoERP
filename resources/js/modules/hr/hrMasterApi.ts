import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { HrMaster } from './hrTypes';

export type HrMasterKind = 'departments' | 'designations' | 'employment-types' | 'skills' | 'certifications' | 'licenses';

export interface HrMasterPayload {
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
    sort_order?: number;
    parent_id?: number | null;
}

const hrMasterEndpoints: Record<HrMasterKind, string> = {
    departments: endpoints.hrDepartments,
    designations: endpoints.hrDesignations,
    'employment-types': endpoints.hrEmploymentTypes,
    skills: endpoints.hrSkills,
    certifications: endpoints.hrCertifications,
    licenses: endpoints.hrLicenses,
};

export function listHrMaster(kind: HrMasterKind, params: ListParams, signal?: AbortSignal): Promise<ApiCollection<HrMaster>> {
    return apiClient.get<ApiCollection<HrMaster>>(hrMasterEndpoints[kind], { params, signal }).then((response) => response.data);
}

export function createHrMaster(kind: HrMasterKind, payload: HrMasterPayload): Promise<HrMaster> {
    return apiClient.post<ApiResource<HrMaster>>(hrMasterEndpoints[kind], payload).then((response) => response.data.data);
}

export function updateHrMaster(kind: HrMasterKind, id: number, payload: HrMasterPayload): Promise<HrMaster> {
    return apiClient.put<ApiResource<HrMaster>>(`${hrMasterEndpoints[kind]}/${id}`, payload).then((response) => response.data.data);
}

export function deleteHrMaster(kind: HrMasterKind, id: number): Promise<void> {
    return apiClient.delete(`${hrMasterEndpoints[kind]}/${id}`).then(() => undefined);
}
