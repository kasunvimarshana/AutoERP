import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { PAGE_SIZE } from './agreements';
import { INCIDENT_API, type IncidentFormData, type IncidentVehicleUseOption, type IncidentHistoryEvent, type RentalIncident, IncidentReviewAction } from './incidentEvidence';

export const listIncidentVehicleUseOptions = (search: string, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<IncidentVehicleUseOption>>(`${INCIDENT_API}/vehicle-use-options`, {
        params: { search: search || undefined, page, per_page: PAGE_SIZE }, signal,
    }).then(result => result.data);

export const listRentalIncidents = (page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalIncident>>(INCIDENT_API, { params: { page, per_page: PAGE_SIZE }, signal }).then(result => result.data);

export const incidentHistory = (incidentId: number, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<IncidentHistoryEvent>>(`${INCIDENT_API}/${incidentId}/history`, {
        params: { page, per_page: PAGE_SIZE }, signal,
    }).then(result => result.data);

export const recordRentalIncident = (data: IncidentFormData) =>
    apiClient.post<ApiResource<RentalIncident>>(INCIDENT_API, data).then(result => result.data.data);

export const reviewRentalIncident = (incident: RentalIncident, action: IncidentReviewAction, reason: string) =>
    apiClient.post<ApiResource<RentalIncident>>(`${INCIDENT_API}/${incident.id}/${action}`, {
        expected_version: incident.row_version,
        reason,
    }).then(result => result.data.data);
