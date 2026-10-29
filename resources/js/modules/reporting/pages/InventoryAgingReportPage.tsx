import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { ExportActions } from '../components/ExportActions';
import { ReportDataGrid } from '../components/ReportDataGrid';
import { runReport, searchAgingLocations, searchAgingWarehouses } from '../reportingApi';
import type { ReportParams, ReportResult } from '../reportingTypes';

const REPORT_KEY = 'inventory.aging';

export default function InventoryAgingReportPage() {
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [params, setParams] = useState<ReportParams>({ page: 1, per_page: 25 });
    const [result, setResult] = useState<ReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const locationSearch = useCallback(
        (lookup: LookupLoadParams) => searchAgingLocations(lookup, warehouse?.id),
        [warehouse?.id],
    );
    const effectiveParams = useMemo<ReportParams>(() => ({
        ...params,
        filters: {
            warehouse_id: warehouse?.id,
            warehouse_location_id: location?.id,
        },
    }), [params, warehouse?.id, location?.id]);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });
        void runReport(REPORT_KEY, effectiveParams, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setResult(data);
            })
            .catch((reason: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(reason));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [effectiveParams]);

    const sort = (column: string) => setParams((current) => ({
        ...current,
        page: 1,
        sort: column,
        direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
    }));

    return <>
        <ContentHeader title="Item Wise Ageing Report" description="Current stock by entry age." actions={<Link className="text-sm text-sky-700 hover:underline" to="/reports">All reports</Link>} />
        <div className="space-y-5">
            <div className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-[1.3fr_1.2fr_1.2fr_1fr_1fr_auto] xl:items-end">
                <Input label="Item Search" value={params.search ?? ''} onChange={(event) => setParams((current) => ({ ...current, page: 1, search: event.target.value }))} />
                <LookupSelect label="Warehouse (optional)" value={warehouse} onChange={(selected) => { setWarehouse(selected); setLocation(null); setParams((current) => ({ ...current, page: 1 })); }} search={searchAgingWarehouses} placeholder="All warehouses" />
                <LookupSelect label="Location (optional)" value={location} onChange={(selected) => { setLocation(selected); setParams((current) => ({ ...current, page: 1 })); }} search={locationSearch} disabled={warehouse === null} placeholder="All locations" />
                <Input label="From (UTC)" type="date" value={params.date_from ?? ''} max={params.date_to} onChange={(event) => setParams((current) => ({ ...current, page: 1, date_from: event.target.value || undefined }))} />
                <Input label="To (UTC)" type="date" value={params.date_to ?? ''} min={params.date_from} onChange={(event) => setParams((current) => ({ ...current, page: 1, date_to: event.target.value || undefined }))} />
                <Button type="button" variant="secondary" onClick={() => { setWarehouse(null); setLocation(null); setParams({ page: 1, per_page: 25 }); }}>Reset</Button>
                {result?.report.date_filter_hint && <p className="text-xs text-slate-500 md:col-span-2 xl:col-span-6">{result.report.date_filter_hint}</p>}
            </div>
            <ErrorAlert error={error} />
            {!result && loading ? <LoadingState label="Loading stock age…" /> : null}
            {result && <>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <span className="text-sm text-slate-500">{loading ? 'Refreshing…' : `${result.meta?.total ?? 0} rows`}</span>
                    {!loading && <ExportActions reportKey={REPORT_KEY} params={effectiveParams} />}
                </div>
                <ReportDataGrid columns={result.report.columns} rows={result.data} sort={params.sort} direction={params.direction} onSort={sort} />
                <Pagination meta={result.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
            </>}
        </div>
    </>;
}
