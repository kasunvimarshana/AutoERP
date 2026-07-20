import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    RentalAgreement,
    RentalAgreementFormLookups,
    RentalAgreementPayload,
    RentalAssignment,
    RentalAssignmentPayload,
    RentalCalculation,
    RentalCustodyPayload,
    RentalRateVersionPayload,
    RentalReplacementPayload,
    RentalRunningChart,
    RentalRunningChartPayload,
} from './vehicleRentalTypes';

const endpoint = '/api/v1/vehicle-rental';

export const getRentalAgreementFormLookups = (signal?: AbortSignal) =>
    apiClient.get<ApiResource<RentalAgreementFormLookups>>(`${endpoint}/lookups/agreement-form`, { signal })
        .then((response) => response.data.data);

export const listRentalAgreements = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalAgreement>>(`${endpoint}/agreements`, { params, signal })
        .then((response) => response.data);

export const listRentalAgreementLookup = (purpose: 'assignment' | 'calculation', params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalAgreement>>(`${endpoint}/lookups/${purpose}-agreements`, { params, signal })
        .then((response) => response.data);

export const getRentalAgreement = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}`, { signal })
        .then((response) => response.data.data);

export const createRentalAgreement = (payload: RentalAgreementPayload) =>
    apiClient.post<ApiResource<RentalAgreement>>(`${endpoint}/agreements`, payload)
        .then((response) => response.data.data);

export const updateRentalAgreement = (id: number, payload: RentalAgreementPayload) =>
    apiClient.put<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}`, payload)
        .then((response) => response.data.data);

export const createRentalRateVersion = (agreementId: number, payload: RentalRateVersionPayload) =>
    apiClient.post<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${agreementId}/rate-versions`, payload)
        .then((response) => response.data.data);

export const activateRentalAgreement = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}/activate`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const closeRentalAgreement = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}/close`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const listRentalAssignments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalAssignment>>(`${endpoint}/assignments`, { params, signal })
        .then((response) => response.data);

export const listRentalAssignmentLookup = (purpose: 'assignment-source' | 'running-chart', params: ListParams, signal?: AbortSignal) => {
    const path = purpose === 'assignment-source' ? 'assignment-sources' : 'running-chart-assignments';
    return apiClient.get<ApiCollection<RentalAssignment>>(`${endpoint}/lookups/${path}`, { params, signal })
        .then((response) => response.data);
};

export const getRentalAssignment = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<RentalAssignment>>(`${endpoint}/assignments/${id}`, { signal })
        .then((response) => response.data.data);

export const createRentalAssignment = (payload: RentalAssignmentPayload) =>
    apiClient.post<ApiResource<RentalAssignment>>(`${endpoint}/assignments`, payload)
        .then((response) => response.data.data);

export const recordRentalCustody = (id: number, payload: RentalCustodyPayload) =>
    apiClient.post<ApiResource<RentalAssignment>>(`${endpoint}/assignments/${id}/custody`, payload)
        .then((response) => response.data.data);

export const replaceRentalAssignment = (id: number, payload: RentalReplacementPayload) =>
    apiClient.post<ApiResource<RentalAssignment>>(`${endpoint}/assignments/${id}/replace`, payload)
        .then((response) => response.data.data);

export const cancelRentalAssignment = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<RentalAssignment>>(`${endpoint}/assignments/${id}/cancel`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const listRentalRunningCharts = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalRunningChart>>(`${endpoint}/running-charts`, { params, signal })
        .then((response) => response.data);

export const getRentalRunningChart = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<RentalRunningChart>>(`${endpoint}/running-charts/${id}`, { signal })
        .then((response) => response.data.data);

export const createRentalRunningChart = (payload: RentalRunningChartPayload) =>
    apiClient.post<ApiResource<RentalRunningChart>>(`${endpoint}/running-charts`, payload)
        .then((response) => response.data.data);

export const updateRentalRunningChart = (id: number, payload: RentalRunningChartPayload) =>
    apiClient.put<ApiResource<RentalRunningChart>>(`${endpoint}/running-charts/${id}`, payload)
        .then((response) => response.data.data);

export const finalizeRentalRunningChart = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<RentalRunningChart>>(`${endpoint}/running-charts/${id}/finalize`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const reverseRentalRunningChart = (id: number, expectedVersion: number, reason: string) =>
    apiClient.post<ApiResource<RentalRunningChart>>(`${endpoint}/running-charts/${id}/reverse`, { expected_version: expectedVersion, reason })
        .then((response) => response.data.data);

export const listRentalCalculations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalCalculation>>(`${endpoint}/calculations`, { params, signal })
        .then((response) => response.data);

export const getRentalCalculation = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<RentalCalculation>>(`${endpoint}/calculations/${id}`, { signal })
        .then((response) => response.data.data);

export const createRentalCalculation = (agreementId: number, payload: { period_start: string; period_end: string }) =>
    apiClient.post<ApiResource<RentalCalculation>>(`${endpoint}/agreements/${agreementId}/calculations`, payload)
        .then((response) => response.data.data);

export const cancelRentalCalculation = (id: number, expectedVersion: number, reason: string) =>
    apiClient.post<ApiResource<RentalCalculation>>(`${endpoint}/calculations/${id}/cancel`, { expected_version: expectedVersion, reason })
        .then((response) => response.data.data);
