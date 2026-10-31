import { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Panel } from '@/shared/components/Panel';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { downloadStockCard, getStockCard, type StockCardFilters, type StockCardResult } from '../inventoryApi';

function currentMonth() {
    const today = new Date();
    const date = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
    return { date_from: `${date.slice(0, 7)}-01`, date_to: date };
}

export default function StockCardPage() {
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const selectedWarehouseId = useRef<number | null>(null);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const [locationReadyFor, setLocationReadyFor] = useState<number | null>(null);
    const [defaultError, setDefaultError] = useState<ApiError | null>(null);
    const [dates, setDates] = useState(currentMonth);
    const [filters, setFilters] = useState<StockCardFilters | null>(null);
    const [loaded, setLoaded] = useState<{ filters: StockCardFilters; result: StockCardResult | null; error: ApiError | null } | null>(null);
    const [exportError, setExportError] = useState<ApiError | null>(null);
    const [exporting, setExporting] = useState<string | null>(null);
    const loading = filters !== null && loaded?.filters !== filters;
    const resolvingLocation = warehouse !== null && locationReadyFor !== warehouse.id;
    const result = loaded?.filters === filters ? loaded.result : null;
    const error = defaultError ?? exportError ?? (loaded?.filters === filters ? loaded.error : null);
    const locationSearch = useCallback(
        (params: LookupLoadParams) => searchWarehouseLocations(params, warehouse?.id),
        [warehouse?.id],
    );

    useEffect(() => {
        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then((defaultWarehouse) => {
                if (!controller.signal.aborted && !warehouseTouched.current && defaultWarehouse) {
                    selectedWarehouseId.current = defaultWarehouse.id;
                    setWarehouse(defaultWarehouse);
                    if ('default_location' in defaultWarehouse) {
                        setLocation(defaultWarehouse.default_location ?? null);
                        setLocationReadyFor(defaultWarehouse.id);
                    }
                }
            })
            .catch((reason: unknown) => {
                if (!controller.signal.aborted) setDefaultError(toApiError(reason));
            });
        return () => controller.abort();
    }, []);

    useEffect(() => {
        if (warehouse === null || locationTouched.current || locationReadyFor === warehouse.id) return;
        const controller = new AbortController();
        void getDefaultWarehouseLocation(warehouse.id, controller.signal)
            .then((defaultLocation) => {
                if (!controller.signal.aborted && selectedWarehouseId.current === warehouse.id && !locationTouched.current && defaultLocation) {
                    setLocation(defaultLocation);
                }
            })
            .catch((reason: unknown) => {
                if (!controller.signal.aborted) setDefaultError(toApiError(reason));
            })
            .finally(() => {
                if (!controller.signal.aborted && selectedWarehouseId.current === warehouse.id) setLocationReadyFor(warehouse.id);
            });
        return () => controller.abort();
    }, [warehouse, locationReadyFor]);

    useEffect(() => {
        if (filters === null) return;
        const controller = new AbortController();
        void getStockCard(filters, controller.signal)
            .then((card) => { if (!controller.signal.aborted) setLoaded({ filters, result: card, error: null }); })
            .catch((reason) => { if (!controller.signal.aborted) setLoaded({ filters, result: null, error: toApiError(reason) }); });
        return () => controller.abort();
    }, [filters]);

    function apply(event: FormEvent) {
        event.preventDefault();
        if (item === null || warehouse === null || resolvingLocation) return;
        setExportError(null);
        setFilters({
            item_id: item.id,
            warehouse_id: warehouse.id,
            warehouse_location_id: location?.id,
            ...dates,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        });
    }

    async function exportFile(format: 'csv' | 'xls' | 'pdf') {
        if (filters === null) return;
        setExporting(format);
        setExportError(null);
        try {
            const blob = await downloadStockCard(filters, format);
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `stock-card-${filters.date_from}-${filters.date_to}.${format}`;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (reason) {
            setExportError(toApiError(reason));
        } finally {
            setExporting(null);
        }
    }

    return <>
        <ContentHeader title="Stock Card" description="Posted stock movements and running balance for one item and warehouse." />
        <Panel title="Select stock and period">
            <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.4fr_1.2fr_1.2fr_1fr_1fr_auto] xl:items-end" onSubmit={apply}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} required placeholder="Search item" />
                <LookupSelect label="Warehouse" value={warehouse} onChange={(selected) => { warehouseTouched.current = true; locationTouched.current = false; selectedWarehouseId.current = selected?.id ?? null; setLocationReadyFor(null); setDefaultError(null); setWarehouse(selected); setLocation(null); }} search={searchWarehouses} required placeholder="Search warehouse" />
                <LookupSelect label="Location (optional)" value={location} onChange={(selected) => { locationTouched.current = true; setLocationReadyFor(warehouse?.id ?? null); setDefaultError(null); setLocation(selected); }} search={locationSearch} disabled={warehouse === null} placeholder="All locations" />
                <Input label="From" type="date" required value={dates.date_from} max={dates.date_to} onChange={(event) => setDates((current) => ({ ...current, date_from: event.target.value }))} />
                <Input label="To" type="date" required value={dates.date_to} min={dates.date_from} onChange={(event) => setDates((current) => ({ ...current, date_to: event.target.value }))} />
                <Button type="submit" disabled={item === null || warehouse === null || resolvingLocation} loading={loading}>{resolvingLocation ? 'Setting location...' : 'View'}</Button>
            </form>
        </Panel>
        <div className="mt-5"><ErrorAlert error={error} /></div>
        {loading ? <LoadingState label="Loading stock card..." /> : null}
        {result && !loading ? <div className="mt-5 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{result.item.code} - {result.item.name} · {result.warehouse.name}{result.location ? ` · ${result.location.name}` : ''}</p>
                    <p className="text-sm text-slate-500">{result.date_from} to {result.date_to} · {result.item.uom ?? 'Base unit'} · Posted date/time shown in {result.timezone}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="secondary" disabled={exporting !== null} onClick={() => void exportFile('csv')}>CSV</Button>
                    <Button type="button" variant="secondary" disabled={exporting !== null} onClick={() => void exportFile('xls')}>Excel</Button>
                    <Button type="button" variant="secondary" disabled={exporting !== null} onClick={() => void exportFile('pdf')}>PDF</Button>
                </div>
            </div>
            <Panel title="Movements">
                <div className="overflow-x-auto">
                    <table className="min-w-full border-collapse text-sm">
                        <thead className="bg-slate-50 text-left text-slate-600"><tr>
                            <th className="px-3 py-3">Posted at</th><th className="px-3 py-3">Document date</th><th className="px-3 py-3">Document</th><th className="px-3 py-3">Description</th><th className="px-3 py-3 text-right">IN</th><th className="px-3 py-3 text-right">OUT</th><th className="px-3 py-3 text-right">Balance</th>
                        </tr></thead>
                        <tbody className="divide-y divide-slate-100">
                            <tr className="bg-slate-50 font-semibold"><td colSpan={6} className="px-3 py-3">Opening balance</td><td className="px-3 py-3 text-right tabular-nums">{result.opening}</td></tr>
                            {result.rows.map((row) => <tr key={row.movement_number}>
                                <td className="whitespace-nowrap px-3 py-3">{new Date(row.posted_at).toLocaleString()}</td>
                                <td className="whitespace-nowrap px-3 py-3">{row.document_date ?? '—'}</td>
                                <td className="px-3 py-3">{row.document?.url ? <Link className="font-medium text-sky-700 hover:underline" to={row.document.url}>{row.document.label}</Link> : row.document?.label ?? row.movement_number}</td>
                                <td className="px-3 py-3">{row.description ?? row.type}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{row.quantity_in === '0.000000' ? '—' : row.quantity_in}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{row.quantity_out === '0.000000' ? '—' : row.quantity_out}</td>
                                <td className="px-3 py-3 text-right font-medium tabular-nums">{row.balance}</td>
                            </tr>)}
                        </tbody>
                        <tfoot className="border-t-2 border-slate-200 bg-slate-50 font-semibold"><tr>
                            <td colSpan={4} className="px-3 py-3">Period totals / closing balance</td>
                            <td className="px-3 py-3 text-right tabular-nums">{result.total_in}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{result.total_out}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{result.closing}</td>
                        </tr></tfoot>
                    </table>
                </div>
                {result.rows.length === 0 ? <p className="mt-4 text-sm text-slate-500">No posted movements in this period.</p> : null}
            </Panel>
        </div> : null}
    </>;
}
