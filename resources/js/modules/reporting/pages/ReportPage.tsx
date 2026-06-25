import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { runReport } from '../reportingApi';
import type { ReportParams, ReportResult } from '../reportingTypes';
import { ExportActions } from '../components/ExportActions';
import { FilterPanel } from '../components/FilterPanel';
import { ReportDataGrid } from '../components/ReportDataGrid';

export default function ReportPage() {
    const key = String(useParams().key ?? '');
    const [params, setParams] = useState<ReportParams>({ page: 1, per_page: 25 });
    const [result, setResult] = useState<ReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const report = result?.report;

    const effectiveParams = useMemo(() => ({
        ...params,
        sort: params.sort ?? report?.default_sort,
        direction: params.direction ?? report?.default_direction,
    }), [params, report?.default_direction, report?.default_sort]);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });
        runReport(key, params, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setResult(data);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [key, params]);

    const sort = (column: string) => {
        setParams((current) => ({
            ...current,
            page: 1,
            sort: column,
            direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
        }));
    };

    if (loading && !result) return <LoadingState label="Loading report..." />;

    return (
        <>
            <ContentHeader
                title={report?.title ?? 'Report'}
                description={report?.group}
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />
            {report && <div className="space-y-5">
                <FilterPanel report={report} value={params} onChange={setParams} onApply={() => setParams((current) => ({ ...current }))} />
                <div className="flex items-center justify-between gap-4">
                    <div className="text-sm text-slate-500">{loading ? 'Refreshing...' : `${result?.meta?.total ?? 0} rows`}</div>
                    <ExportActions reportKey={key} params={effectiveParams} />
                </div>
                <ReportDataGrid columns={report.columns} rows={result?.data ?? []} sort={effectiveParams.sort} direction={effectiveParams.direction} onSort={sort} />
                <Pagination meta={result?.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
            </div>}
        </>
    );
}
