import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { SupplierLookupSelect, WarehouseLookupSelect } from '@/modules/purchase/components/PurchaseLookups';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import type { NamedResource } from '@/shared/types/common';
import { formatMoney } from '@/shared/utils/formatMoney';
import { ExportActions } from '../components/ExportActions';
import { runGoodsReceiptSummaryReport } from '../reportingApi';
import type { GoodsReceiptSummaryParams, GoodsReceiptSummaryResult, GoodsReceiptSummaryRow } from '../goodsReceiptSummaryTypes';

const REPORT_KEY = 'purchase/goods-receipt-summary';
const INITIAL_PARAMS: GoodsReceiptSummaryParams = { page: 1, per_page: 25 };

interface LocationGroup {
    key: string;
    locationId: number | null;
    location: string;
    currency: string;
    receipts: GoodsReceiptSummaryRow[];
}

export default function GoodsReceiptSummaryReportPage() {
    const [params, setParams] = useState<GoodsReceiptSummaryParams>(INITIAL_PARAMS);
    const [draft, setDraft] = useState<GoodsReceiptSummaryParams>(INITIAL_PARAMS);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [result, setResult] = useState<GoodsReceiptSummaryResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });
        runGoodsReceiptSummaryReport(params, controller.signal)
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
    }, [params]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setParams({ ...draft, page: 1 });
    };

    const reset = () => {
        setSupplier(null);
        setWarehouse(null);
        setDraft(INITIAL_PARAMS);
        setParams(INITIAL_PARAMS);
    };

    const groups = groupReceipts(result?.data ?? []);

    return <>
        <ContentHeader
            title="Goods Receipt Note Summary Report"
            description="Posted goods receipts by location, with accepted quantity and cost."
            actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
        />
        <ErrorAlert error={error} />

        <form onSubmit={apply}>
            <Panel title="Find goods receipts">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input label="GRN, PO, supplier or note" value={draft.search ?? ''} onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value || undefined }))} />
                    <Input label="GRN date from" type="date" value={draft.date_from ?? ''} max={draft.date_to} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value || undefined }))} />
                    <Input label="GRN date to" type="date" value={draft.date_to ?? ''} min={draft.date_from} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value || undefined }))} />
                    <SupplierLookupSelect value={supplier} onChange={(value) => {
                        setSupplier(value);
                        setDraft((current) => ({ ...current, supplier_id: value?.id ?? null }));
                    }} loadOnOpen minSearchLength={0} />
                    <WarehouseLookupSelect value={warehouse} onChange={(value) => {
                        setWarehouse(value);
                        setDraft((current) => ({ ...current, warehouse_id: value?.id ?? null }));
                    }} />
                </div>
                <p className="mt-3 text-sm text-slate-600">Dates use the GRN receipt date. Posted GRNs only. Cost is accepted quantity × unit cost before discounts, tax and charges. Quantity totals require the same UOM.</p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <Button type="submit">Apply filters</Button>
                    <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                </div>
            </Panel>
        </form>

        {!result && loading && <LoadingState label="Building goods receipt summary..." />}
        {result && <div className="mt-5 space-y-5">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <p className="text-sm text-slate-600">{loading ? 'Refreshing…' : `${result.summary.total_receipts} posted GRNs`}</p>
                <ExportActions reportKey={REPORT_KEY} params={params} />
            </div>

            {groups.length === 0 && <div className="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600">No posted goods receipts matched these filters.</div>}
            {groups.map((group) => {
                const total = result.summary.locations.find((item) => item.location_id === group.locationId && item.currency === group.currency);
                return <section key={group.key} className="overflow-hidden rounded-lg border border-slate-300 bg-white">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 bg-slate-100 px-4 py-3">
                        <h2 className="font-semibold text-slate-900">{group.location}</h2>
                        <span className="text-sm text-slate-600">{group.currency}</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[940px] border-collapse text-sm">
                            <thead className="border-b border-slate-300 bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="px-4 py-2">GRN</th>
                                    <th className="px-3 py-2">GRN date</th>
                                    <th className="px-3 py-2">Supplier</th>
                                    <th className="px-3 py-2">PO</th>
                                    <th className="px-3 py-2">Note</th>
                                    <th className="px-3 py-2 text-right">Accepted qty</th>
                                    <th className="px-4 py-2 text-right">Total cost</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {group.receipts.map((receipt) => <tr key={receipt.id}>
                                    <td className="whitespace-nowrap px-4 py-3"><Link to={`/purchase/goods-receipts/${receipt.id}`} className="font-semibold text-sky-800 underline-offset-2 hover:underline">{receipt.grn_number}</Link></td>
                                    <td className="whitespace-nowrap px-3 py-3 text-slate-700">{receipt.received_date}</td>
                                    <td className="px-3 py-3 text-slate-800">{receipt.supplier}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{receipt.purchase_order ? <Link to={`/purchase/orders/${receipt.purchase_order.id}`} className="text-sky-800 underline-offset-2 hover:underline">{receipt.purchase_order.number}</Link> : '—'}</td>
                                    <td className="max-w-64 whitespace-pre-wrap break-words px-3 py-3 text-slate-700">{receipt.note || '—'}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right tabular-nums text-slate-700">{quantity(receipt.accepted_quantity, receipt.uom)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums font-medium text-slate-900">{formatMoney(receipt.total_cost, group.currency)}</td>
                                </tr>)}
                            </tbody>
                            {total && <tfoot className="border-t-2 border-slate-400 bg-slate-50 font-semibold text-slate-900">
                                <tr>
                                    <td colSpan={5} className="px-4 py-3">{group.location} total · {total.receipt_count} GRNs across all matching pages</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right tabular-nums">{quantity(total.accepted_quantity, total.uom)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums">{formatMoney(total.total_cost, group.currency)}</td>
                                </tr>
                            </tfoot>}
                        </table>
                    </div>
                </section>;
            })}

            {result.summary.currencies.length > 0 && <div className="border-y-2 border-slate-400 bg-slate-100 px-4 py-3">
                <h2 className="mb-2 font-semibold text-slate-900">Grand total</h2>
                <div className="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    {result.summary.currencies.map((total) => <div key={total.currency} className="flex flex-wrap gap-x-5 gap-y-1">
                        <span className="font-medium text-slate-700">{total.currency} · {total.receipt_count} GRNs</span>
                        <span className="text-slate-700">Accepted qty <strong className="tabular-nums text-slate-900">{quantity(total.accepted_quantity, total.uom)}</strong></span>
                        <span className="text-slate-700">Total cost <strong className="tabular-nums text-slate-900">{formatMoney(total.total_cost, total.currency)}</strong></span>
                    </div>)}
                </div>
            </div>}
            <Pagination meta={result.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
        </div>}
    </>;
}

function groupReceipts(receipts: GoodsReceiptSummaryRow[]): LocationGroup[] {
    const groups: LocationGroup[] = [];
    for (const receipt of receipts) {
        const key = `${receipt.location_id ?? 'none'}:${receipt.currency}`;
        const last = groups.at(-1);
        if (last?.key === key) {
            last.receipts.push(receipt);
        } else {
            groups.push({ key, locationId: receipt.location_id, location: receipt.location, currency: receipt.currency, receipts: [receipt] });
        }
    }
    return groups;
}

function quantity(value: string | null, uom: string | null): string {
    if (value === null) return 'Mixed units';
    const formatted = Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 3 });
    return `${formatted}${uom ? ` ${uom}` : ''}`;
}
