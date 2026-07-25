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
    SummaryReportResult,
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

export async function exportReport(
    key: string,
    format: ReportFormat,
    params: ReportParams | TechnicianWorkReportParams | EmployeeCommissionReportParams | OperationalReportParams,
    previewWindow?: Window | null,
): Promise<void> {
    const response = await apiClient.get<Blob>(`${endpoints.reports}/${key}/export/${format}`, {
        params,
        responseType: 'blob',
    });
    const url = URL.createObjectURL(response.data);

    if (format === 'print' || format === 'html') {
        if (!previewWindow || previewWindow.closed) {
            URL.revokeObjectURL(url);
            throw new Error('The report preview window was blocked. Allow popups for AutoERP and try again.');
        }

        previewWindow.opener = null;
        previewWindow.location.replace(url);
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
        return;
    }

    const link = document.createElement('a');
    link.href = url;
    link.download = responseFilename(response.headers['content-disposition'])
        ?? `${key.replaceAll('/', '-').replaceAll('.', '-')}.${format}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
}

function responseFilename(contentDisposition?: string): string | null {
    if (!contentDisposition) return null;

    const encodedMatch = contentDisposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (encodedMatch?.[1]) {
        try {
            return sanitizeFilename(decodeURIComponent(encodedMatch[1].trim().replace(/^"|"$/g, '')));
        } catch {
            // Fall through to the plain filename parameter.
        }
    }

    const plainMatch = contentDisposition.match(/filename\s*=\s*(?:"([^"]+)"|([^;]+))/i);
    const filename = plainMatch?.[1] ?? plainMatch?.[2];
    return filename ? sanitizeFilename(filename.trim()) : null;
}

function sanitizeFilename(filename: string): string | null {
    const sanitized = filename
        .replace(/[\\/:*?"<>|\u0000-\u001F]/g, '_')
        .replace(/^\.+/, '')
        .trim();

    return sanitized !== '' ? sanitized.slice(0, 240) : null;
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

export async function runSummaryReport(
    params: { date_from: string; date_to: string },
    signal?: AbortSignal,
): Promise<SummaryReportResult> {
    const response = await apiClient.get<ApiResource<SummaryReportResult>>(`${endpoints.reports}/summary`, {
        params,
        signal,
    });

    return response.data.data;
}
