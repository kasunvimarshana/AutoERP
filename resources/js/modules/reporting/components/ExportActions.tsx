import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { exportReport } from '../reportingApi';
import { reportingPermissions } from '../reportingPermissions';
import type { EmployeeCommissionReportParams, GrnPayablesReportParams, OperationalReportParams, PaymentMethodWiseParams, ReportFormat, ReportParams, TechnicianWorkReportParams, VehicleServiceHistoryParams } from '../reportingTypes';
import type { GoodsReceiptSummaryParams } from '../goodsReceiptSummaryTypes';
import type { VehicleServiceSalesSummaryParams } from '../vehicleServiceSalesSummaryTypes';

const defaultFormats: ReportFormat[] = ['html', 'print', 'pdf', 'xlsx', 'csv'];

export function ExportActions({ reportKey, params, formats = defaultFormats, compact = false }: { reportKey: string; params: ReportParams | TechnicianWorkReportParams | EmployeeCommissionReportParams | OperationalReportParams | GrnPayablesReportParams | GoodsReceiptSummaryParams | VehicleServiceHistoryParams | PaymentMethodWiseParams | VehicleServiceSalesSummaryParams; formats?: ReportFormat[]; compact?: boolean }) {
    const auth = useAuth();
    const [busy, setBusy] = useState<ReportFormat | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    if (!hasPermission(auth, reportingPermissions.export)) return null;

    const run = async (format: ReportFormat) => {
        setBusy(format);
        setError(null);
        const opensPreview = format === 'html' || format === 'print';
        const previewWindow = opensPreview ? window.open('', '_blank') : null;
        if (opensPreview && !previewWindow) {
            setBusy(null);
            setError(new ApiError('The report preview was blocked. Allow popups for AutoERP and try again.', null));
            return;
        }

        try {
            if (previewWindow) {
                previewWindow.document.title = 'Preparing report…';
                previewWindow.document.body.textContent = 'Preparing report…';
            }
            await exportReport(reportKey, format, params, previewWindow);
        } catch (requestError) {
            previewWindow?.close();
            setError(toApiError(requestError));
        } finally {
            setBusy(null);
        }
    };

    const actions = (
        <div className="flex flex-wrap gap-2">
            {formats.map((format) => (
                <Button key={format} type="button" variant="secondary" loading={busy === format} onClick={() => void run(format)}>
                    {label(format)}
                </Button>
            ))}
        </div>
    );

    return (
        <div className="space-y-2">
            <ErrorAlert error={error} />
            {compact ? <details className="relative">
                <summary className="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">Export</summary>
                <div className="absolute right-0 z-10 mt-2 min-w-max rounded-lg border border-slate-200 bg-white p-3 shadow-lg">{actions}</div>
            </details> : actions}
        </div>
    );
}

function label(format: ReportFormat): string {
    return {
        html: 'Preview',
        print: 'Print',
        pdf: 'PDF',
        xlsx: 'Excel',
        csv: 'CSV',
    }[format];
}
