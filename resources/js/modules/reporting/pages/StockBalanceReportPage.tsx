import { Fragment, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Pagination } from '@/shared/components/Pagination';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { ExportActions } from '../components/ExportActions';
import { loadStockBalanceDetails, runStockBalanceReport } from '../reportingApi';
import type { StockBalanceDetail, StockBalanceReportParams, StockBalanceReportResult, StockBalanceReportRow } from '../stockBalanceReportTypes';

const REPORT_KEY = 'inventory.stock-balance';

function quantity(value: string | number): string {
    const [whole, fraction = ''] = String(value).split('.');
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const decimals = fraction.replace(/0+$/, '');
    return decimals ? `${grouped}.${decimals}` : grouped;
}

function rowKey(row: StockBalanceReportRow): string {
    return [row.item_id, row.item_variant_id ?? '', row.base_uom_id ?? '', row.warehouse_id, row.location_id ?? ''].join(':');
}

function ItemLabel({ row }: { row: StockBalanceReportRow }) {
    return <div className="min-w-0">
        <div className="font-semibold text-slate-900">{row.item_name}</div>
        {row.variant_name && <div className="text-sm text-slate-700">{row.variant_name}</div>}
        <div className="text-xs text-slate-500">{row.item_code}{row.variant_code ? ` · ${row.variant_code}` : ''} · {row.warehouse}{row.location ? ` / ${row.location}` : ''} · {row.uom ?? 'Unit not set'}</div>
    </div>;
}

function StockValue({ value, tone = 'normal' }: { value: string | number; tone?: 'normal' | 'good' | 'warning' | 'danger' }) {
    const color = tone === 'good' ? 'text-emerald-800' : tone === 'warning' ? 'text-amber-800' : tone === 'danger' ? 'text-rose-700' : 'text-slate-900';
    return <span className={`font-bold tabular-nums ${color}`}>{quantity(value)}</span>;
}

function StockBreakdown({ details }: { details: StockBalanceDetail[] }) {
    return <div className="rounded-lg border border-slate-200 bg-white p-3">
        <h3 className="mb-2 text-sm font-semibold text-slate-900">Stock breakdown by batch</h3>
        <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
                <thead className="bg-slate-50 text-left text-slate-600"><tr>
                    {['Batch', 'Current', 'Available', 'Reserved', 'Allocated', 'Returned', 'In transit', 'Damaged', 'Quarantine', 'Expired', 'Scrapped'].map((label) => <th key={label} className="px-3 py-2">{label}</th>)}
                </tr></thead>
                <tbody className="divide-y divide-slate-100">{details.map((detail, index) => <tr key={`${detail.batch_number ?? 'none'}-${index}`}>
                    <td className="px-3 py-2">{detail.batch_number ?? 'No batch'}</td>
                    {(['quantity_on_hand', 'quantity_available', 'quantity_reserved', 'quantity_allocated', 'quantity_returned', 'quantity_in_transit', 'quantity_damaged', 'quantity_quarantine', 'quantity_expired', 'quantity_scrapped'] as const).map((field) => <td key={field} className="px-3 py-2 text-right tabular-nums">{quantity(detail[field])}</td>)}
                </tr>)}</tbody>
            </table>
        </div>
    </div>;
}

export default function StockBalanceReportPage() {
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [params, setParams] = useState<StockBalanceReportParams>({ page: 1, per_page: 25, include_zero: true });
    const [result, setResult] = useState<StockBalanceReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [openKey, setOpenKey] = useState<string | null>(null);
    const [details, setDetails] = useState<{ key: string; rows: StockBalanceDetail[]; error: ApiError | null } | null>(null);
    const locationSearch = useCallback((lookup: LookupLoadParams) => searchWarehouseLocations(lookup, warehouse?.id), [warehouse?.id]);
    const filters = useMemo(() => ({ ...params, warehouse_id: warehouse?.id, location_id: location?.id }), [params, warehouse?.id, location?.id]);
    const openRow = result?.data.find((row) => rowKey(row) === openKey);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => { if (!controller.signal.aborted) setLoading(true); });
        void runStockBalanceReport(filters, controller.signal)
            .then((data) => { if (!controller.signal.aborted) { setResult(data); setError(null); } })
            .catch((reason) => { if (!controller.signal.aborted) setError(toApiError(reason)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters]);

    useEffect(() => {
        if (!openRow || !openKey) return;
        const controller = new AbortController();
        void loadStockBalanceDetails(openRow, controller.signal)
            .then((rows) => { if (!controller.signal.aborted) setDetails({ key: openKey, rows, error: null }); })
            .catch((reason) => { if (!controller.signal.aborted) setDetails({ key: openKey, rows: [], error: toApiError(reason) }); });
        return () => controller.abort();
    }, [openKey, openRow]);

    const resetOpen = () => { setOpenKey(null); setDetails(null); };

    return <>
        <ContentHeader title="Stock Balance" description="Current item quantities by warehouse and location." actions={<Link to="/reports" className="text-sm text-sky-700 hover:underline">All reports</Link>} />
        <div className="space-y-5">
            <div className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto_auto] xl:items-end">
                <Input label="Item search" placeholder="Name, code or SKU" value={params.search ?? ''} onChange={(event) => { resetOpen(); setParams((current) => ({ ...current, page: 1, search: event.target.value })); }} />
                <LookupSelect label="Warehouse" value={warehouse} onChange={(selected) => { resetOpen(); setWarehouse(selected); setLocation(null); setParams((current) => ({ ...current, page: 1 })); }} search={searchWarehouses} placeholder="All warehouses" loadOnOpen minSearchLength={0} />
                <LookupSelect label="Location" value={location} onChange={(selected) => { resetOpen(); setLocation(selected); setParams((current) => ({ ...current, page: 1 })); }} search={locationSearch} disabled={!warehouse} placeholder="All locations" loadOnOpen minSearchLength={0} />
                <label className="flex min-h-10 items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={params.include_zero} onChange={(event) => { resetOpen(); setParams((current) => ({ ...current, page: 1, include_zero: event.target.checked })); }} />Include zero stock</label>
                <Button type="button" variant="secondary" onClick={() => { resetOpen(); setWarehouse(null); setLocation(null); setParams({ page: 1, per_page: 25, include_zero: true }); }}>Reset</Button>
            </div>
            <ErrorAlert error={error} />
            {!result && loading && <LoadingState label="Loading stock balances..." />}
            {result && <>
                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                    <span>{loading ? 'Refreshing...' : `${result.meta?.total ?? 0} item/location rows`} · Current balance, generated {new Date(result.generated_at).toLocaleString()}</span>
                    <ExportActions reportKey={REPORT_KEY} params={filters} />
                </div>
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50 text-left text-slate-600"><tr><th className="min-w-64 px-4 py-3">Item</th><th className="px-4 py-3 text-right">Current stock</th><th className="px-4 py-3 text-right">Available to sell</th><th className="px-4 py-3 text-right">Damaged</th><th className="px-4 py-3 text-right">Expired</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{result.data.map((row) => {
                            const key = rowKey(row);
                            const expanded = key === openKey;
                            return <Fragment key={key}>
                                <tr className="hover:bg-slate-50">
                                    <td className="px-4 py-3"><button type="button" aria-expanded={expanded} className="flex w-full items-start gap-2 text-left hover:text-blue-700" onClick={() => { setOpenKey(expanded ? null : key); setDetails(null); }}><span aria-hidden="true">{expanded ? '▾' : '▸'}</span><ItemLabel row={row} /></button></td>
                                    <td className="px-4 py-3 text-right"><StockValue value={row.quantity_on_hand} /></td>
                                    <td className="px-4 py-3 text-right"><StockValue value={row.quantity_available} tone={row.stock_level === 'in_stock' ? 'good' : row.stock_level === 'low_stock' ? 'warning' : 'danger'} /><div className="text-xs text-slate-500">{row.stock_level === 'in_stock' ? 'In stock' : row.stock_level === 'low_stock' ? 'Low stock' : 'Out of stock'}</div></td>
                                    <td className="px-4 py-3 text-right">{Number(row.quantity_damaged) === 0 ? '—' : <StockValue value={row.quantity_damaged} tone="warning" />}</td>
                                    <td className="px-4 py-3 text-right">{Number(row.quantity_expired) === 0 ? '—' : <StockValue value={row.quantity_expired} tone="danger" />}</td>
                                </tr>
                                {expanded && <tr><td colSpan={5} className="bg-slate-50 px-4 py-4">{details?.key !== key ? <span className="text-slate-500">Loading breakdown...</span> : details.error ? <ErrorAlert error={details.error} /> : <StockBreakdown details={details.rows} />}</td></tr>}
                            </Fragment>;
                        })}</tbody>
                    </table>
                    {result.data.length === 0 && <p className="px-4 py-10 text-center text-slate-500">No stock balances match these filters.</p>}
                </div>
                <Pagination meta={result.meta} onPageChange={(page) => { resetOpen(); setParams((current) => ({ ...current, page })); }} />
            </>}
        </div>
    </>;
}
