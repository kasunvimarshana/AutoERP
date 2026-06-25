import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { exportReport } from '../reportingApi';
import { reportingPermissions } from '../reportingPermissions';
import type { EmployeeCommissionReportParams, OperationalReportParams, ReportFormat, ReportParams, TechnicianWorkReportParams } from '../reportingTypes';

export function ExportActions({ reportKey, params }: { reportKey: string; params: ReportParams | TechnicianWorkReportParams | EmployeeCommissionReportParams | OperationalReportParams }) {
    const auth = useAuth();
    const [busy, setBusy] = useState<ReportFormat | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    if (!auth.permissions.includes(reportingPermissions.export)) return null;

    const run = async (format: ReportFormat) => {
        setBusy(format);
        setError(null);
        try {
            await exportReport(reportKey, format, params);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(null);
        }
    };

    return (
        <div className="space-y-2">
            <ErrorAlert error={error} />
            <div className="flex flex-wrap gap-2">
                {(['html', 'print', 'pdf', 'xlsx', 'csv'] as ReportFormat[]).map((format) => (
                    <Button key={format} type="button" variant="secondary" loading={busy === format} onClick={() => void run(format)}>
                        {label(format)}
                    </Button>
                ))}
            </div>
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
