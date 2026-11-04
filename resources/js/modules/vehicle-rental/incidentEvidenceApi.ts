import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { PAGE_SIZE } from './agreements';
import { INCIDENT_API, type IncidentFormData, type IncidentAttachment, type IncidentVehicleUseOption, type IncidentRunningChartOption, type IncidentHistoryEvent, type RentalIncident, IncidentReviewAction } from './incidentEvidence';

export const listIncidentVehicleUseOptions = (search: string, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<IncidentVehicleUseOption>>(`${INCIDENT_API}/vehicle-use-options`, {
        params: { search: search || undefined, page, per_page: PAGE_SIZE }, signal,
    }).then(result => result.data);

export const listIncidentRunningCharts = (vehicleUseId: number, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<IncidentRunningChartOption>>(
        `${INCIDENT_API}/vehicle-uses/${vehicleUseId}/running-chart-options`,
        { params: { page, per_page: PAGE_SIZE }, signal },
    ).then(result => result.data);

export const listRentalIncidents = (page: number, signal?: AbortSignal, search?: string) =>
    apiClient.get<ApiCollection<RentalIncident>>(INCIDENT_API, {
        params: { page, per_page: PAGE_SIZE, search: search || undefined },
        signal,
    }).then(result => result.data);

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

export async function attachIncidentFile(incident: RentalIncident, file: File): Promise<void> {
    const data = new FormData();
    data.append('expected_version', String(incident.row_version));
    data.append('file', file);
    await apiClient.post(`${INCIDENT_API}/${incident.id}/attachments`, data);
}

export async function downloadIncidentFile(incidentId: number, attachment: IncidentAttachment): Promise<void> {
    const response = await apiClient.get<Blob>(`${INCIDENT_API}/${incidentId}/attachments/${attachment.id}`, { responseType: 'blob' });
    const url = URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = attachment.original_filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
