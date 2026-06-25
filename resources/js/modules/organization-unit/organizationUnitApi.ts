import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

const organizationUnitsEndpoint = '/api/v1/organization-units';
const organizationUnitTypesEndpoint = '/api/v1/organization-unit-types';

export interface OrganizationUnitType {
    id: number;
    name: string;
    level: number;
    is_active: boolean;
    row_version: number;
    organization_unit_count?: number;
}

export interface OrganizationUnitSummary {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    type_id: number;
    parent_id: number | null;
    path: string;
    depth: number;
    is_root: boolean;
    is_active: boolean;
    retired_at?: string | null;
    row_version: number;
    lifecycle_status: 'active' | 'inactive' | 'retired';
    has_logo?: boolean;
    type?: Pick<OrganizationUnitType, 'id' | 'name' | 'level' | 'is_active'> | null;
    parent?: { id: number; name: string; code: string; path: string; depth: number; is_active: boolean; retired_at?: string | null } | null;
}

export interface OrganizationUnitDocument {
    id: number;
    name: string;
    document_type?: string | null;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    checksum_sha256: string;
    scanned_at: string;
    row_version: number;
}

interface OrganizationUnitBasePayload {
    name: string;
    description?: string | null;
    type_id: number;
    parent_id?: number;
}

export interface OrganizationUnitCreatePayload extends OrganizationUnitBasePayload {
    code: string;
}

export interface OrganizationUnitUpdatePayload extends OrganizationUnitBasePayload {
    expected_version: number;
}

export interface OrganizationUnitTypePayload {
    name: string;
    level: number;
    is_active: boolean;
    expected_version?: number;
}

export const organizationUnitApi = {
    async list(params: ListParams, signal?: AbortSignal): Promise<ApiCollection<OrganizationUnitSummary>> {
        const response = await apiClient.get<ApiCollection<OrganizationUnitSummary>>(organizationUnitsEndpoint, { params, signal });
        return response.data;
    },

    async searchParentCandidates(
        params: { search: string; page: number; perPage: number; targetUnitId?: number },
        signal: AbortSignal,
    ): Promise<ApiCollection<OrganizationUnitSummary>> {
        const response = await apiClient.get<ApiCollection<OrganizationUnitSummary>>(organizationUnitsEndpoint, {
            params: {
                search: params.search || undefined,
                page: params.page,
                per_page: params.perPage,
                is_active: true,
                parent_candidates_for: params.targetUnitId,
            },
            signal,
        });
        return response.data;
    },

    async get(id: number, signal?: AbortSignal): Promise<OrganizationUnitSummary> {
        const response = await apiClient.get<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${id}`, { signal });
        return response.data.data;
    },

    async create(payload: OrganizationUnitCreatePayload): Promise<OrganizationUnitSummary> {
        const response = await apiClient.post<ApiResource<OrganizationUnitSummary>>(organizationUnitsEndpoint, payload);
        return response.data.data;
    },

    async update(id: number, payload: OrganizationUnitUpdatePayload): Promise<OrganizationUnitSummary> {
        const response = await apiClient.put<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${id}`, payload);
        return response.data.data;
    },

    async activate(unit: OrganizationUnitSummary): Promise<OrganizationUnitSummary> {
        const response = await apiClient.patch<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${unit.id}/activate`, {
            expected_version: unit.row_version,
        });
        return response.data.data;
    },

    async deactivate(unit: OrganizationUnitSummary): Promise<OrganizationUnitSummary> {
        const response = await apiClient.patch<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${unit.id}/deactivate`, {
            expected_version: unit.row_version,
        });
        return response.data.data;
    },

    async retire(unit: OrganizationUnitSummary): Promise<OrganizationUnitSummary> {
        const response = await apiClient.patch<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${unit.id}/retire`, {
            expected_version: unit.row_version,
        });
        return response.data.data;
    },

    async replaceLogo(unit: OrganizationUnitSummary, logo: File): Promise<OrganizationUnitSummary> {
        const form = new FormData();
        form.append('_method', 'PUT');
        form.append('expected_version', String(unit.row_version));
        form.append('logo', logo);
        const response = await apiClient.post<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${unit.id}/logo`, form);
        return response.data.data;
    },

    async removeLogo(unit: OrganizationUnitSummary): Promise<OrganizationUnitSummary> {
        const response = await apiClient.delete<ApiResource<OrganizationUnitSummary>>(`${organizationUnitsEndpoint}/${unit.id}/logo`, {
            data: { expected_version: unit.row_version },
        });
        return response.data.data;
    },

    async listTypes(signal?: AbortSignal): Promise<OrganizationUnitType[]> {
        const response = await apiClient.get<ApiCollection<OrganizationUnitType>>(organizationUnitTypesEndpoint, { signal });
        return response.data.data;
    },

    async createType(payload: OrganizationUnitTypePayload): Promise<OrganizationUnitType> {
        const response = await apiClient.post<ApiResource<OrganizationUnitType>>(organizationUnitTypesEndpoint, payload);
        return response.data.data;
    },

    async updateType(id: number, payload: OrganizationUnitTypePayload): Promise<OrganizationUnitType> {
        const response = await apiClient.put<ApiResource<OrganizationUnitType>>(`${organizationUnitTypesEndpoint}/${id}`, payload);
        return response.data.data;
    },

    async deleteType(type: OrganizationUnitType): Promise<void> {
        await apiClient.delete(`${organizationUnitTypesEndpoint}/${type.id}`, {
            data: { expected_version: type.row_version },
        });
    },

    async listDocuments(unitId: number, params: ListParams, signal?: AbortSignal): Promise<ApiCollection<OrganizationUnitDocument>> {
        const response = await apiClient.get<ApiCollection<OrganizationUnitDocument>>(
            `${organizationUnitsEndpoint}/${unitId}/documents`,
            { params, signal },
        );
        return response.data;
    },

    async createDocument(unitId: number, payload: { name: string; document_type?: string | null; file: File }): Promise<OrganizationUnitDocument> {
        const form = new FormData();
        form.append('name', payload.name);
        if (payload.document_type) form.append('document_type', payload.document_type);
        form.append('file', payload.file);
        const response = await apiClient.post<ApiResource<OrganizationUnitDocument>>(
            `${organizationUnitsEndpoint}/${unitId}/documents`,
            form,
        );
        return response.data.data;
    },

    async updateDocument(unitId: number, document: OrganizationUnitDocument, payload: { name: string; document_type?: string | null; file?: File }): Promise<OrganizationUnitDocument> {
        const form = new FormData();
        form.append('_method', 'PUT');
        form.append('expected_version', String(document.row_version));
        form.append('name', payload.name);
        if (payload.document_type) form.append('document_type', payload.document_type);
        if (payload.file) form.append('file', payload.file);
        const response = await apiClient.post<ApiResource<OrganizationUnitDocument>>(
            `${organizationUnitsEndpoint}/${unitId}/documents/${document.id}`,
            form,
        );
        return response.data.data;
    },

    async deleteDocument(unitId: number, document: OrganizationUnitDocument): Promise<void> {
        await apiClient.delete(`${organizationUnitsEndpoint}/${unitId}/documents/${document.id}`, {
            data: { expected_version: document.row_version },
        });
    },

    async downloadDocument(unitId: number, document: OrganizationUnitDocument): Promise<void> {
        const response = await apiClient.get<Blob>(
            `${organizationUnitsEndpoint}/${unitId}/documents/${document.id}/download`,
            { responseType: 'blob' },
        );
        const url = URL.createObjectURL(response.data);
        try {
            const anchor = window.document.createElement('a');
            anchor.href = url;
            anchor.download = document.original_filename;
            window.document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        } finally {
            URL.revokeObjectURL(url);
        }
    },
};
