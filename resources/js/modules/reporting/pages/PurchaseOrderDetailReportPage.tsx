import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { SupplierLookupSelect } from '@/modules/purchase/components/PurchaseLookups';
import { ExportActions } from '../components/ExportActions';
import { runPurchaseOrderDetailReport } from '../reportingApi';
import type { OperationalReportParams } from '../operationalReportTypes';
import type { PurchaseOrderDetailOrder, PurchaseOrderDetailResult } from '../purchaseOrderDetailTypes';

const REPORT_KEY = 'purchase/detailed';
const INITIAL_PARAMS: OperationalReportParams = { page: 1, per_page: 25 };
const STATUS_OPTIONS = [
    { value: '', label: 'All statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending_approval', label: 'Pending approval' },
    { value: 'approved', label: 'Approved' },
    { value: 'closed', label: 'Closed' },
    { value: 'cancelled', label: 'Cancelled' },
];

interface SupplierGroup {
    key: string;
    name: string;
    currency: string;
    supplierId: number | null;
    orders: PurchaseOrderDetailOrder[];
}

export default function PurchaseOrderDetailReportPage() {
    const [params, setParams] = useState<OperationalReportParams>(INITIAL_PARAMS);
    const [draft, setDraft] = useState<OperationalReportParams>(INITIAL_PARAMS);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [result, setResult] = useState<PurchaseOrderDetailResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) {
                setLoading(true);
                setError(null);
            }
        });
        runPurchaseOrderDetailReport(params, controller.signal)
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
        setDraft(INITIAL_PARAMS);
        setParams(INITIAL_PARAMS);
    };

    if (loading && !result) return <LoadingState label="Building purchase order report..." />;

    const groups = groupOrders(result?.data ?? []);

    return (
        <>
            <ContentHeader
                title="Purchase Order Detail Report"
                description="See every item and receipt total within its purchase order."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />

            <form onSubmit={apply}>
                <Panel title="Find purchase orders">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input label="PO, supplier or item" value={draft.search ?? ''} onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))} />
                        <Input label="PO date from" type="date" value={draft.date_from ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value || undefined }))} />
                        <Input label="PO date to" type="date" value={draft.date_to ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value || undefined }))} />
                        <Select label="PO status" value={draft.purchase_status ?? ''} options={STATUS_OPTIONS} onChange={(event) => setDraft((current) => ({ ...current, purchase_status: event.target.value || undefined }))} />
                        <SupplierLookupSelect
                            value={supplier}
                            onChange={(value) => {
                                setSupplier(value);
                                setDraft((current) => ({ ...current, supplier_id: value?.id ?? null }));
                            }}
                            loadOnOpen
                            minSearchLength={0}
                        />
                    </div>
                    <p className="mt-3 text-sm text-slate-600">Dates select POs; posted GRN receipts show to date. GRN cost is accepted qty × unit cost before adjustments. Returns remain part of received history. Item search keeps each PO complete.</p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                    </div>
                </Panel>
            </form>

            {result && <div className="mt-5 space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <p className="text-sm text-slate-600">{loading ? 'Refreshing…' : `${result.summary.total_orders} purchase orders`}</p>
                    <ExportActions reportKey={REPORT_KEY} params={params} />
                </div>

                {result.summary.currencies.length > 0 && <div className="flex flex-wrap gap-x-8 gap-y-2 border-y border-slate-300 bg-slate-50 px-4 py-3 text-sm">
                    {result.summary.currencies.map((total) => <div key={total.currency} className="flex flex-wrap gap-x-5 gap-y-1">
                        <strong className="text-slate-800">{total.currency}</strong>
                        <span>PO amount <strong className="tabular-nums text-slate-900">{money(total.po_amount, total.currency)}</strong></span>
                        <span>GRN cost <strong className="tabular-nums text-slate-900">{money(total.grn_cost, total.currency)}</strong></span>
                        {total.ordered_quantity !== null && <span>Qty <strong className="tabular-nums text-slate-900">{quantity(total.ordered_quantity, total.uom)} ordered / {quantity(total.grn_quantity ?? '0', total.uom)} GRN</strong></span>}
                    </div>)}
                </div>}

                {groups.length === 0 && <div className="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600">No purchase orders matched these filters.</div>}
                {groups.map((group) => {
                    const supplierTotal = result.summary.suppliers.find((total) => total.supplier_id === group.supplierId && total.currency === group.currency);
                    return <section key={group.key} className="overflow-hidden rounded-lg border border-slate-300 bg-white">
                        <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 bg-slate-100 px-4 py-3">
                            <h2 className="text-base font-semibold text-slate-900">{group.name}</h2>
                            <span className="text-sm text-slate-600">{group.currency}</span>
                        </div>
                        <div className="divide-y divide-slate-200">
                            {group.orders.map((order) => <PurchaseOrderBlock key={order.id} order={order} />)}
                        </div>
                        {supplierTotal && <div className="flex flex-wrap justify-between gap-2 border-t-2 border-slate-300 bg-slate-50 px-4 py-3 text-sm">
                            <span className="font-semibold text-slate-700">Supplier total for all {supplierTotal.order_count} matching POs</span>
                            <span className="tabular-nums font-semibold text-slate-900">
                                {supplierTotal.ordered_quantity !== null && `${quantity(supplierTotal.ordered_quantity, supplierTotal.uom)} ordered · ${quantity(supplierTotal.grn_quantity ?? '0', supplierTotal.uom)} GRN · `}
                                GRN cost {money(supplierTotal.grn_cost, group.currency)}
                            </span>
                        </div>}
                    </section>;
                })}
                <Pagination meta={result.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
            </div>}
        </>
    );
}

function PurchaseOrderBlock({ order }: { order: PurchaseOrderDetailOrder }) {
    return <div className="px-4 py-4">
        <div className="flex flex-wrap items-start justify-between gap-x-5 gap-y-2">
            <div>
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <Link to={`/purchase/orders/${order.id}`} className="font-semibold text-sky-800 underline-offset-2 hover:underline">{order.purchase_order_number}</Link>
                    <span className="text-sm text-slate-600">{order.purchase_order_date}</span>
                    <span className="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{statusLabel(order.status)}</span>
                    <span className="text-sm text-slate-600">{order.status_action.label} <strong className="font-medium text-slate-800">{order.status_action.actor?.name ?? 'Not recorded'}</strong></span>
                </div>
                {order.warehouse && <p className="mt-1 text-xs text-slate-500">Warehouse: {order.warehouse}</p>}
            </div>
            <div className="text-sm text-slate-600">PO amount <strong className="ml-1 tabular-nums text-slate-900">{money(order.po_amount, order.currency)}</strong></div>
        </div>

        <div className="mt-3 overflow-x-auto">
            <table className="w-full min-w-[680px] border-collapse text-sm">
                <thead className="border-y border-slate-300 bg-slate-50 text-left text-xs font-semibold text-slate-600">
                    <tr>
                        <th className="py-2 pr-3">Item</th>
                        <th className="px-2 py-2 text-right">Cost price</th>
                        <th className="px-2 py-2 text-right">Order qty</th>
                        <th className="px-2 py-2 text-right">GRN qty</th>
                        <th className="py-2 pl-2 text-right">Total GRN cost</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {order.items.length === 0 && <tr><td colSpan={5} className="py-4 text-center text-slate-500">No items on this purchase order.</td></tr>}
                    {order.items.map((item) => <tr key={item.id}>
                        <td className="py-2 pr-3 text-slate-900">
                            <span className="font-medium">{item.description}</span>
                            {(item.item_code || item.variant) && <span className="ml-2 text-xs text-slate-500">{[item.item_code, item.variant].filter(Boolean).join(' · ')}</span>}
                        </td>
                        <td className="px-2 py-2 text-right tabular-nums text-slate-700">{formatDecimal(item.unit_price)}</td>
                        <td className="px-2 py-2 text-right tabular-nums text-slate-700">{quantity(item.ordered_quantity, item.uom)}</td>
                        <td className="px-2 py-2 text-right tabular-nums text-slate-700">{quantity(item.grn_quantity, item.uom)}</td>
                        <td className="py-2 pl-2 text-right tabular-nums font-medium text-slate-900">{formatDecimal(item.grn_cost)}</td>
                    </tr>)}
                </tbody>
                <tfoot className="border-t-2 border-slate-400 bg-slate-50 font-semibold text-slate-900">
                    <tr>
                        <td className="py-2 pr-3">{order.purchase_order_number} total</td>
                        <td />
                        <td className="px-2 py-2 text-right tabular-nums">{order.ordered_quantity === null ? (order.items.length === 0 ? '—' : 'Mixed units') : quantity(order.ordered_quantity, order.uom)}</td>
                        <td className="px-2 py-2 text-right tabular-nums">{order.grn_quantity === null ? (order.items.length === 0 ? '—' : 'Mixed units') : quantity(order.grn_quantity, order.uom)}</td>
                        <td className="py-2 pl-2 text-right tabular-nums">{formatDecimal(order.grn_cost)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>;
}

function groupOrders(orders: PurchaseOrderDetailOrder[]): SupplierGroup[] {
    const groups: SupplierGroup[] = [];
    for (const order of orders) {
        const key = `${order.supplier.id ?? 'none'}:${order.currency}`;
        const last = groups.at(-1);
        if (last?.key === key) {
            last.orders.push(order);
        } else {
            groups.push({ key, name: order.supplier.name, currency: order.currency, supplierId: order.supplier.id, orders: [order] });
        }
    }
    return groups;
}

function statusLabel(status: string): string {
    const text = status.replaceAll('_', ' ');
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function quantity(value: string, uom: string | null): string {
    return `${formatDecimal(value)}${uom ? ` ${uom}` : ''}`;
}

function money(value: string, currency: string): string {
    return `${currency} ${formatDecimal(value)}`;
}

function formatDecimal(value: string): string {
    const match = value.match(/^([+-]?)(\d+)(?:\.(\d+))?$/);
    if (!match) return value;
    const fraction = (match[3] ?? '').replace(/0+$/, '').padEnd(2, '0');
    return `${match[1]}${match[2].replace(/\B(?=(\d{3})+(?!\d))/g, ',')}.${fraction}`;
}
