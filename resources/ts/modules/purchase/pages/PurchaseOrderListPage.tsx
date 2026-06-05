import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseDashboard, PurchaseOrder, PurchasePage } from '../types/purchase.types';

export function PurchaseOrderListPage() {
    const [data, setData] = useState<PurchasePage<PurchaseOrder> | null>(null);
    const [dashboard, setDashboard] = useState<PurchaseDashboard | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => { void purchaseApi.dashboard().then(setDashboard).catch(() => undefined); }, []);
    useEffect(() => { const timer = window.setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 350); return () => window.clearTimeout(timer); }, [searchInput]);
    useEffect(() => {
        let active = true;
        setLoading(true);
        void purchaseApi.listOrders({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => { if (active) setData(response); })
            .catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load purchase orders.'); })
            .finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [page, search, status]);

    async function remove(order: PurchaseOrder) {
        if (!window.confirm(`Delete draft PO ${order.poNumber}?`)) return;
        try {
            await purchaseApi.removeOrder(order.id);
            setData((current) => current ? { ...current, items: current.items.filter((item) => item.id !== order.id), meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) } } : current);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to delete purchase order.');
        }
    }

    return <div className="space-y-5">
        <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Procurement</p><h1 className="mt-1 text-3xl font-bold text-slate-950">Purchase orders</h1><p className="mt-1 text-sm text-slate-500">PO to GRN to invoice, with stock and finance handled by their own modules.</p></div><div className="flex gap-2"><Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/purchase/grns">GRNs</Link><Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/purchase/returns">Returns</Link><Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to="/purchase/orders/new">Create PO</Link></div></header>
        {dashboard ? <div className="grid gap-3 md:grid-cols-4"><Metric label="Open POs" value={dashboard.open_purchase_orders} /><Metric label="Pending GRNs" value={dashboard.pending_grns} /><Metric label="Supplier outstanding" value={Number(dashboard.supplier_outstanding).toLocaleString()} /><Metric label="Unpaid invoices" value={Number(dashboard.unpaid_purchase_invoices.amount).toLocaleString()} /></div> : null}
        <div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px]"><Input placeholder="Search PO, reference, or supplier" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value); }}><option value="">All statuses</option><option value="draft">Draft</option><option value="confirmed">Confirmed</option><option value="partially_received">Partially received</option><option value="received">Received</option><option value="closed">Closed</option><option value="cancelled">Cancelled</option></select></div>
        {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">{loading ? <Loading label="Loading purchase orders" /> : data?.items.length ? <div className="overflow-x-auto"><table className="w-full min-w-[900px] text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">PO</th><th className="px-4 py-3">Supplier</th><th className="px-4 py-3">Dates</th><th className="px-4 py-3">Total</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead><tbody className="divide-y divide-slate-100">{data.items.map((order) => <tr key={order.id} className="hover:bg-slate-50/70"><td className="px-4 py-4 font-semibold text-slate-900">{order.poNumber}</td><td className="px-4 py-4 text-slate-600">{order.supplierName ?? `#${order.supplierId}`}</td><td className="px-4 py-4 text-slate-600"><p>{order.orderDate}</p><p className="text-xs">Expected {order.expectedDate ?? 'not set'}</p></td><td className="px-4 py-4 font-semibold text-slate-900">{Number(order.grandTotal).toLocaleString()}</td><td className="px-4 py-4"><Badge label={order.status} /></td><td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/purchase/orders/${order.id}`}>View</Link>{order.status === 'draft' ? <Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/purchase/orders/${order.id}/edit`}>Edit</Link> : null}{order.status === 'draft' ? <button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(order)} type="button">Delete</button> : null}</div></td></tr>)}</tbody></table></div> : <Empty label="No purchase orders found" />}</div>
        {data ? <Pager current={data.meta.currentPage} last={data.meta.lastPage} loading={loading} setPage={setPage} total={data.meta.total} /> : null}
    </div>;
}

export function Badge({ label }: { label: string }) { return <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{label.replaceAll('_', ' ')}</span>; }
export function Empty({ label }: { label: string }) { return <div className="p-12 text-center"><p className="font-semibold text-slate-800">{label}</p><p className="mt-1 text-sm text-slate-500">Adjust filters or create a new record.</p></div>; }
export function Loading({ label }: { label: string }) { return <div className="flex items-center justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">{label}</span></div>; }
function Metric({ label, value }: { label: string; value: string | number }) { return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><p className="mt-2 text-2xl font-bold text-slate-950">{value}</p></div>; }
function Pager({ current, last, loading, setPage, total }: { current: number; last: number; loading: boolean; setPage: (page: number | ((current: number) => number)) => void; total: number }) { return <div className="flex items-center justify-between text-sm text-slate-500"><span>{total} total</span><div className="flex gap-2"><Button disabled={current <= 1 || loading} onClick={() => setPage((page) => page - 1)} variant="secondary">Previous</Button><span className="flex items-center px-2">Page {current} of {Math.max(1, last)}</span><Button disabled={current >= last || loading} onClick={() => setPage((page) => page + 1)} variant="secondary">Next</Button></div></div>; }
