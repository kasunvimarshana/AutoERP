import { useEffect, useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseOrder } from '../types/purchase.types';
import { Badge, Loading } from './PurchaseOrderListPage';
import { Alert, Header, Section } from './PurchaseOrderEditorPage';

export function PurchaseOrderDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [order, setOrder] = useState<PurchaseOrder | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => { if (id) void purchaseApi.getOrder(Number(id)).then(setOrder).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load purchase order.')); }, [id]);
    async function confirm() { if (!order) return; setBusy(true); try { setOrder(await purchaseApi.confirmOrder(order.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to confirm purchase order.'); } finally { setBusy(false); } }
    async function close() { if (!order || !window.confirm(`Close PO ${order.poNumber}? Further receiving will stop.`)) return; setBusy(true); try { setOrder(await purchaseApi.closeOrder(order.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to close purchase order.'); } finally { setBusy(false); } }

    if (!order && !error) return <Loading label="Loading purchase order" />;
    return <div className="space-y-5"><Header title={order ? order.poNumber : 'Purchase order'} />{error ? <Alert message={error} /> : null}{order ? <>
        <div className="flex flex-wrap gap-2"><Button onClick={() => navigate('/purchase/orders')} variant="secondary">Back</Button>{order.status === 'draft' ? <Button disabled={busy} onClick={() => void confirm()} variant="blue">Confirm PO</Button> : null}{order.status === 'draft' ? <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={`/purchase/orders/${order.id}/edit`}>Edit</Link> : null}{['confirmed', 'partially_received'].includes(order.status) ? <Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to={`/purchase/grns/new?purchaseOrderId=${order.id}`}>Create GRN</Link> : null}{['confirmed', 'partially_received', 'received'].includes(order.status) ? <Button disabled={busy} onClick={() => void close()} variant="secondary">Close PO</Button> : null}</div>
        <Section title="Summary"><div className="grid gap-4 md:grid-cols-4"><Info label="Supplier" value={order.supplierName ?? `#${order.supplierId}`} /><Info label="Warehouse" value={order.warehouseName ?? `#${order.warehouseId}`} /><Info label="Status" value={<Badge label={order.status} />} /><Info label="Grand total" value={Number(order.grandTotal).toLocaleString()} /><Info label="Supplier outstanding" value={order.supplierBalance ?? '0.0000'} /></div></Section>
        <Section title="Lines"><Table rows={order.lines ?? []} /></Section>
        <Section title="GRNs"><div className="space-y-2">{order.grns?.length ? order.grns.map((grn: any) => <Link className="block rounded-lg border border-slate-100 p-3 text-sm hover:bg-slate-50" key={grn.id} to={`/purchase/grns/${grn.id}`}>{grn.grn_number} · {grn.status} · {Number(grn.grand_total ?? 0).toLocaleString()}</Link>) : <p className="text-sm text-slate-500">No GRNs yet.</p>}</div></Section>
    </> : null}</div>;
}

function Info({ label, value }: { label: string; value: ReactNode }) { return <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><div className="mt-1 text-sm font-semibold text-slate-800">{value}</div></div>; }
function Table({ rows }: { rows: any[] }) { return <div className="overflow-x-auto"><table className="w-full min-w-[700px] text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-3 py-2">Item</th><th className="px-3 py-2">Ordered</th><th className="px-3 py-2">Received</th><th className="px-3 py-2">Invoiced</th><th className="px-3 py-2">Total</th></tr></thead><tbody>{rows.map((line) => <tr className="border-b border-slate-100" key={line.id}><td className="px-3 py-3">{line.item_code} - {line.item_name}</td><td className="px-3 py-3">{line.ordered_qty}</td><td className="px-3 py-3">{line.received_qty}</td><td className="px-3 py-3">{line.invoiced_qty}</td><td className="px-3 py-3">{line.line_total_with_tax}</td></tr>)}</tbody></table></div>; }
