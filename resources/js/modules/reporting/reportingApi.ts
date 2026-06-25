import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type {
    EmployeeCommissionReportParams,
    OperationalReportParams,
    OperationalReportResult,
    EmployeeCommissionReportResult,
    ReportDefinition,
    ReportFormat,
    ReportParams,
    ReportResult,
    TechnicianWorkReportParams,
    TechnicianWorkReportResult,
} from './reportingTypes';

export async function listReports(signal?: AbortSignal): Promise<ReportDefinition[]> {
    const response = await apiClient.get<ApiResource<ReportDefinition[]>>(endpoints.reports, { signal });
    return response.data.data;
}

export async function getReport(key: string, signal?: AbortSignal): Promise<ReportDefinition> {
    const response = await apiClient.get<ApiResource<ReportDefinition>>(`${endpoints.reports}/${key}`, { signal });
    return response.data.data;
}

export async function runReport(key: string, params: ReportParams, signal?: AbortSignal): Promise<ReportResult> {
    const response = await apiClient.get<ReportResult>(`${endpoints.reports}/${key}/run`, { params, signal });
    return response.data;
}

export async function exportReport(key: string, format: ReportFormat, params: ReportParams | TechnicianWorkReportParams | EmployeeCommissionReportParams | OperationalReportParams): Promise<void> {
    const response = await apiClient.get<Blob>(`${endpoints.reports}/${key}/export/${format}`, {
        params,
        responseType: 'blob',
    });
    const blob = response.data;
    const url = URL.createObjectURL(blob);

    if (format === 'print' || format === 'html') {
        window.open(url, '_blank', 'noopener,noreferrer');
        setTimeout(() => URL.revokeObjectURL(url), 60_000);
        return;
    }

    const link = document.createElement('a');
    link.href = url;
    link.download = responseFilename(response.headers['content-disposition'])
        ?? `${key.replaceAll('/', '-').replaceAll('.', '-')}.${format}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function responseFilename(contentDisposition?: string): string | null {
    const match = contentDisposition?.match(/filename="?([^";]+)"?/i);
    return match?.[1] ?? null;
}

export async function runTechnicianWorkReport(params: TechnicianWorkReportParams, signal?: AbortSignal): Promise<TechnicianWorkReportResult> {
    const response = await apiClient.get<TechnicianWorkReportResult>(`${endpoints.reports}/vehicle-service/technician-work`, { params, signal });
    return response.data;
}

export async function runEmployeeCommissionReport(params: EmployeeCommissionReportParams, signal?: AbortSignal): Promise<EmployeeCommissionReportResult> {
    const response = await apiClient.get<EmployeeCommissionReportResult>(`${endpoints.reports}/vehicle-service/employee-commissions`, { params, signal });
    return response.data;
}

export async function runOperationalReport(key: string, params: OperationalReportParams, signal?: AbortSignal): Promise<OperationalReportResult> {
    const response = await apiClient.get<OperationalReportResult>(`${endpoints.reports}/${key}`, { params, signal });
    return response.data;
}
