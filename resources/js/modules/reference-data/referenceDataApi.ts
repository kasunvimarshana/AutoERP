import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { ReferenceCatalog, ReferenceRecord } from './referenceDataTypes';

export async function listReferenceRecords(
    catalog: ReferenceCatalog,
    search: string,
    page: number,
    signal?: AbortSignal,
) {
    const response = await apiClient.get<ApiCollection<ReferenceRecord>>(`/api/v1/${catalog}`, {
        params: { search: search || undefined, page, per_page: 25 },
        signal,
    });

    return response.data;
}

export async function listActiveReferenceRecords(
    catalog: ReferenceCatalog,
    signal?: AbortSignal,
) {
    const response = await apiClient.get<ApiCollection<ReferenceRecord>>(
        `/api/v1/${catalog}/lookup`,
        { params: { page: 1, per_page: 1000 }, signal },
    );

    return response.data.data;
}

export async function createReferenceRecord(
    catalog: ReferenceCatalog,
    payload: Record<string, unknown>,
) {
    const response = await apiClient.post<ApiResource<ReferenceRecord>>(
        `/api/v1/${catalog}`,
        payload,
    );

    return response.data.data;
}

export async function updateReferenceRecord(
    catalog: ReferenceCatalog,
    record: ReferenceRecord,
    payload: Record<string, unknown>,
) {
    const response = await apiClient.put<ApiResource<ReferenceRecord>>(
        `/api/v1/${catalog}/${record.id}`,
        { ...payload, expected_version: record.row_version },
    );

    return response.data.data;
}

export async function setReferenceRecordStatus(
    catalog: ReferenceCatalog,
    record: ReferenceRecord,
    isActive: boolean,
) {
    const response = await apiClient.patch<ApiResource<ReferenceRecord>>(
        `/api/v1/${catalog}/${record.id}/status`,
        { expected_version: record.row_version, is_active: isActive },
    );

    return response.data.data;
}
