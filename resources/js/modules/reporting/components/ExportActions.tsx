import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { exportReport } from '../reportingApi';
import type { ReportFormat, ReportParams, TechnicianWorkReportParams } from '../reportingTypes';

export function ExportActions({ reportKey, params }: { reportKey: string; params: ReportParams | TechnicianWorkReportParams }) {
    const [busy, setBusy] = useState<ReportFormat | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
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
                {(['csv', 'xlsx', 'pdf', 'print'] as ReportFormat[]).map((format) => (
                    <Button key={format} type="button" variant="secondary" loading={busy === format} onClick={() => void run(format)}>
                        {format.toUpperCase()}
                    </Button>
                ))}
            </div>
        </div>
    );
}
