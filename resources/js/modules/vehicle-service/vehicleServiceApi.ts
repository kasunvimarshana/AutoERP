import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    VehicleServiceDocument,
    VehicleServiceEmployeeAssignment,
    VehicleServiceInspection,
    VehicleServiceJob,
    VehicleServiceJobLine,
    VehicleServiceJobPayload,
    VehicleServiceLinePayload,
    VehicleServiceStatusHistory,
} from './vehicleServiceTypes';

const jobs = `${endpoints.vehicleService}/jobs`;

export const listVehicleServiceJobs = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJob>>(jobs, { params, signal }).then((response) => response.data);

export const getVehicleServiceJob = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceJob>>(`${jobs}/${id}`, { signal }).then((response) => response.data.data);

export const createVehicleServiceJob = (payload: VehicleServiceJobPayload) =>
    apiClient.post<ApiResource<VehicleServiceJob>>(jobs, payload).then((response) => response.data.data);

export const updateVehicleServiceJob = (id: number, payload: VehicleServiceJobPayload) =>
    apiClient.put<ApiResource<VehicleServiceJob>>(`${jobs}/${id}`, payload).then((response) => response.data.data);

export const deleteVehicleServiceJob = (id: number) => apiClient.delete(`${jobs}/${id}`);

export const inspectVehicleServiceJob = (id: number, payload: Partial<VehicleServiceInspection>) =>
    apiClient.patch<ApiResource<VehicleServiceInspection>>(`${jobs}/${id}/inspect`, payload).then((response) => response.data.data);

export const startVehicleServiceJob = (id: number) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/start`).then((response) => response.data.data);

export const completeVehicleServiceJob = (id: number) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/complete`).then((response) => response.data.data);

export const cancelVehicleServiceJob = (id: number, reason?: string) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/cancel`, { reason }).then((response) => response.data.data);

export const getVehicleServiceInspection = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceInspection | null>>(`${jobs}/${jobId}/inspection`, { signal }).then((response) => response.data.data);

export const saveVehicleServiceInspection = (jobId: number, payload: Partial<VehicleServiceInspection>) =>
    apiClient.put<ApiResource<VehicleServiceInspection>>(`${jobs}/${jobId}/inspection`, payload).then((response) => response.data.data);

export const listVehicleServiceLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, { signal }).then((response) => response.data.data);

export const createVehicleServiceLine = (jobId: number, payload: VehicleServiceLinePayload) =>
    apiClient.post<ApiResource<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, payload).then((response) => response.data.data);

export const updateVehicleServiceLine = (jobId: number, lineId: number, payload: VehicleServiceLinePayload) =>
    apiClient.put<ApiResource<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines/${lineId}`, payload).then((response) => response.data.data);

export const deleteVehicleServiceLine = (jobId: number, lineId: number) =>
    apiClient.delete(`${jobs}/${jobId}/lines/${lineId}`);

export const listEmployeeAssignableLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/employee-assignable-lines`, { signal }).then((response) => response.data.data);

export const createVehicleServiceEmployee = (jobId: number, lineId: number, payload: Record<string, unknown>) =>
    apiClient.post<ApiResource<VehicleServiceEmployeeAssignment>>(`${jobs}/${jobId}/lines/${lineId}/employees`, payload).then((response) => response.data.data);

export const updateVehicleServiceEmployee = (jobId: number, lineId: number, assignmentId: number, payload: Record<string, unknown>) =>
    apiClient.put<ApiResource<VehicleServiceEmployeeAssignment>>(`${jobs}/${jobId}/lines/${lineId}/employees/${assignmentId}`, payload).then((response) => response.data.data);

export const deleteVehicleServiceEmployee = (jobId: number, lineId: number, assignmentId: number) =>
    apiClient.delete(`${jobs}/${jobId}/lines/${lineId}/employees/${assignmentId}`);

export const listInventoryIssueLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/inventory-issue-lines`, { signal }).then((response) => response.data.data);

export const issueVehicleServiceInventory = (jobId: number, payload: { warehouse_id: number; warehouse_location_id?: number; line_ids?: number[] }) =>
    apiClient.post<ApiResource<Array<Record<string, unknown>>>>(`${jobs}/${jobId}/issue-inventory`, payload).then((response) => response.data.data);

export const listBillableLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/billable-lines`, { signal }).then((response) => response.data.data);

export const previewVehicleServiceInvoice = (jobId: number, payload: Record<string, unknown>) =>
    apiClient.post<ApiResource<Record<string, unknown>>>(`${jobs}/${jobId}/invoices/preview`, payload).then((response) => response.data.data);

export const createVehicleServiceInvoice = (jobId: number, payload: Record<string, unknown>) =>
    apiClient.post<ApiResource<Record<string, unknown>>>(`${jobs}/${jobId}/invoices`, payload).then((response) => response.data.data);

export const prepareVehicleServicePayment = (jobId: number, payload: Record<string, unknown>) =>
    apiClient.post<ApiResource<Record<string, unknown>>>(`${jobs}/${jobId}/payments/prepare`, payload).then((response) => response.data.data);

export const listVehicleServiceDocuments = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, { signal }).then((response) => response.data.data);

export const createVehicleServiceDocument = (jobId: number, payload: FormData) =>
    apiClient.post<ApiResource<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, payload).then((response) => response.data.data);

export const deleteVehicleServiceDocument = (jobId: number, documentId: number) =>
    apiClient.delete(`${jobs}/${jobId}/documents/${documentId}`);

export const listVehicleServiceStatusHistory = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceStatusHistory[]>>(`${jobs}/${jobId}/status-history`, { signal }).then((response) => response.data.data);
