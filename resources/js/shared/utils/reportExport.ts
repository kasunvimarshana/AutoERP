import { apiClient } from '@/shared/api/apiClient';

export interface ReportExportOptions {
    url: string;
    params?: Record<string, unknown> | URLSearchParams;
    format: 'print' | 'html' | 'pdf' | string;
    previewWindow?: Window | null;
    filename?: string;
}

export async function exportReportBlob({
    url,
    params,
    format,
    previewWindow,
    filename,
}: ReportExportOptions): Promise<void> {
    try {
        const response = await apiClient.get<Blob>(url, {
            params,
            responseType: 'blob',
        });

        const rawContentType = response.headers ? response.headers['content-type'] : undefined;
        const contentType = typeof rawContentType === 'string' && rawContentType !== ''
            ? rawContentType
            : (format === 'pdf' ? 'application/pdf' : 'text/html');
        const blob = new Blob([response.data], { type: contentType });
        const blobUrl = URL.createObjectURL(blob);

        if (format === 'print' || format === 'html') {
            if (previewWindow && !previewWindow.closed) {
                previewWindow.opener = null;
                previewWindow.location.replace(blobUrl);
            } else {
                const opened = window.open(blobUrl, '_blank');
                if (!opened && previewWindow === null) {
                    URL.revokeObjectURL(blobUrl);
                    throw new Error('The report preview was blocked. Allow popups for AutoERP and try again.');
                }
            }
            window.setTimeout(() => URL.revokeObjectURL(blobUrl), 120_000);
            return;
        }

        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename || (format === 'pdf' ? 'report.pdf' : `report.${format}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(blobUrl), 120_000);
    } catch (error) {
        if (previewWindow && !previewWindow.closed) {
            previewWindow.close();
        }
        throw error;
    }
}
