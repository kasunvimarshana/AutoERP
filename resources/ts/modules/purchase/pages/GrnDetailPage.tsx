import { useEffect, useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { purchaseApi } from '../services/purchaseApi';
import type { Grn } from '../types/purchase.types';
import { Badge, Loading } from './PurchaseOrderListPage';
import { Alert, Header, Section } from './PurchaseOrderEditorPage';

export function GrnDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [grn, setGrn] = useState<Grn | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState('');
    useEffect(() => { if (id) void purchaseApi.getGrn(Number(id)).then(setGrn).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load GRN.')); }, [id]);
    async function post() { if (!grn) return; setBusy('post'); try { setGrn(await purchaseApi.postGrn(grn.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to post GRN.'); } finally { setBusy(''); } }
    async function invoice() { if (!grn) return; setBusy('invoice'); try { await purchaseApi.invoiceGrn(grn.id); setGrn(await purchaseApi.getGrn(grn.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to create purchase invoice.'); } finally { setBusy(''); } }
    if (!grn && !error) return <Loading label="Loading GRN" />;
    const invoiceLink = grn?.invoiceLinks?.[0];
    return <div className="space-y-5"><Header title={grn ? grn.grnNumber : 'GRN'} />{error ? <Alert message={error} /> : null}{grn ? <><div className="flex flex-wrap gap-2"><Button onClick={() => navigate('/purchase/grns')} variant="secondary">Back</Button>{grn.status === 'draft' ? <Button disabled={busy === 'post'} onClick={() => void post()} variant="blue">Post to inventory</Button> : null}{grn.status === 'draft' ? <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={`/purchase/grns/${grn.id}/edit`}>Edit</Link> : null}{grn.status === 'posted' && !invoiceLink ? <Button disabled={busy === 'invoice'} onClick={() => void invoice()} variant="blue">Create purchase invoice</Button> : null}{grn.status === 'posted' ? <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={`/purchase/returns/new?grnId=${grn.id}`}>Create return</Link> : null}</div><Section title="Summary"><div className="grid gap-4 md:grid-cols-4"><Info label="Supplier" value={grn.supplierName ?? `#${grn.supplierId}`} /><Info label="PO" value={grn.poNumber ?? `#${grn.purchaseOrderId}`} /><Info label="Status" value={<Badge label={grn.status} />} /><Info label="Grand total" value={Number(grn.grandTotal).toLocaleString()} /></div></Section><Section title="Lines"><LineTable rows={grn.lines ?? []} /></Section>{invoiceLink ? <Section title="Purchase Invoice & Payment"><div className="grid gap-4 md:grid-cols-4"><Info label="Invoice" value={invoiceLink.invoice_number} /><Info label="Status" value={invoiceLink.status} /><Info label="Balance" value={Number(invoiceLink.balance_total).toLocaleString()} /><Info label="Total" value={Number(invoiceLink.grand_total).toLocaleString()} /></div>{Number(invoiceLink.balance_total) > 0 ? <div className="mt-4"><Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to="/payments/new">Pay in Payment module</Link></div> : null}</Section> : null}</> : null}</div>;
}

function Info({ label, value }: { label: string; value: ReactNode }) { return <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><div className="mt-1 text-sm font-semibold text-slate-800">{value}</div></div>; }
function LineTable({ rows }: { rows: any[] }) { return <table className="w-full text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-3 py-2">Item</th><th className="px-3 py-2">Received</th><th className="px-3 py-2">Returned</th><th className="px-3 py-2">Invoiced</th></tr></thead><tbody>{rows.map((line) => <tr className="border-b border-slate-100" key={line.id}><td className="px-3 py-3">{line.item_code} - {line.item_name}</td><td className="px-3 py-3">{line.accepted_qty}</td><td className="px-3 py-3">{line.returned_qty}</td><td className="px-3 py-3">{line.invoiced_qty}</td></tr>)}</tbody></table>; }
