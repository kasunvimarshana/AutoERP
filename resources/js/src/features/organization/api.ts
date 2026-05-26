import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    OrganizationUnitListFilters,
    OrganizationUnitPayload,
    OrganizationUnitRecord,
    OrganizationUnitTypeListFilters,
    OrganizationUnitTypePayload,
    OrganizationUnitTypeRecord,
    OrganizationUnitUserAssignment,
    OrganizationUnitUserPayload,
} from './types';

export const organizationApi = {
    listOrganizationUnits(filters: OrganizationUnitListFilters): Promise<PaginatedResult<OrganizationUnitRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<OrganizationUnitRecord>>('/organization-units', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<OrganizationUnitRecord>(payload));
    },
    getOrganizationUnit(organizationUnitId: number, include?: string) {
        return apiClient
            .get<ApiResourceEnvelope<OrganizationUnitRecord> | OrganizationUnitRecord>(`/organization-units/${organizationUnitId}`, {
                query: include ? { include } : undefined,
            })
            .then((payload) => unwrapResource<OrganizationUnitRecord>(payload));
    },
    createOrganizationUnit(payload: OrganizationUnitPayload) {
        return apiClient
            .post<ApiResourceEnvelope<OrganizationUnitRecord> | OrganizationUnitRecord>('/organization-units', payload)
            .then((result) => unwrapResource<OrganizationUnitRecord>(result));
    },
    updateOrganizationUnit(organizationUnitId: number, payload: Partial<OrganizationUnitPayload>) {
        return apiClient
            .put<ApiResourceEnvelope<OrganizationUnitRecord> | OrganizationUnitRecord>(`/organization-units/${organizationUnitId}`, payload)
            .then((result) => unwrapResource<OrganizationUnitRecord>(result));
    },
    deleteOrganizationUnit(organizationUnitId: number) {
        return apiClient.delete<{ message: string }>(`/organization-units/${organizationUnitId}`);
    },
    listOrganizationUnitTypes(filters: OrganizationUnitTypeListFilters): Promise<PaginatedResult<OrganizationUnitTypeRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<OrganizationUnitTypeRecord>>('/organization-unit-types', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<OrganizationUnitTypeRecord>(payload));
    },
    getOrganizationUnitType(organizationUnitTypeId: number) {
        return apiClient
            .get<ApiResourceEnvelope<OrganizationUnitTypeRecord> | OrganizationUnitTypeRecord>(`/organization-unit-types/${organizationUnitTypeId}`)
            .then((payload) => unwrapResource<OrganizationUnitTypeRecord>(payload));
    },
    createOrganizationUnitType(payload: OrganizationUnitTypePayload) {
        return apiClient
            .post<ApiResourceEnvelope<OrganizationUnitTypeRecord> | OrganizationUnitTypeRecord>('/organization-unit-types', payload)
            .then((result) => unwrapResource<OrganizationUnitTypeRecord>(result));
    },
    updateOrganizationUnitType(organizationUnitTypeId: number, payload: Partial<OrganizationUnitTypePayload>) {
        return apiClient
            .put<ApiResourceEnvelope<OrganizationUnitTypeRecord> | OrganizationUnitTypeRecord>(`/organization-unit-types/${organizationUnitTypeId}`, payload)
            .then((result) => unwrapResource<OrganizationUnitTypeRecord>(result));
    },
    deleteOrganizationUnitType(organizationUnitTypeId: number) {
        return apiClient.delete<{ message: string }>(`/organization-unit-types/${organizationUnitTypeId}`);
    },
    listOrganizationUnitUsers(organizationUnitId: number, tenantId: number): Promise<PaginatedResult<OrganizationUnitUserAssignment>> {
        return apiClient
            .get<ApiPaginatedEnvelope<OrganizationUnitUserAssignment>>(`/organization-units/${organizationUnitId}/users`, {
                query: { tenant_id: tenantId, per_page: 100, page: 1 },
            })
            .then((payload) => unwrapPaginated<OrganizationUnitUserAssignment>(payload));
    },
    createOrganizationUnitUser(organizationUnitId: number, payload: OrganizationUnitUserPayload) {
        return apiClient
            .post<ApiResourceEnvelope<OrganizationUnitUserAssignment> | OrganizationUnitUserAssignment>(`/organization-units/${organizationUnitId}/users`, payload)
            .then((result) => unwrapResource<OrganizationUnitUserAssignment>(result));
    },
    updateOrganizationUnitUser(organizationUnitId: number, organizationUnitUserId: number, payload: Partial<OrganizationUnitUserPayload>) {
        return apiClient
            .put<ApiResourceEnvelope<OrganizationUnitUserAssignment> | OrganizationUnitUserAssignment>(
                `/organization-units/${organizationUnitId}/users/${organizationUnitUserId}`,
                payload,
            )
            .then((result) => unwrapResource<OrganizationUnitUserAssignment>(result));
    },
    deleteOrganizationUnitUser(organizationUnitId: number, organizationUnitUserId: number) {
        return apiClient.delete<{ message: string }>(`/organization-units/${organizationUnitId}/users/${organizationUnitUserId}`);
    },
};
