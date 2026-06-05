import { useEffect, useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseReturn } from '../types/purchase.types';
import { Badge, Loading } from './PurchaseOrderListPage';
import { Alert, Header, Section } from './PurchaseOrderEditorPage';

export function PurchaseReturnDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [ret, setReturn] = useState<PurchaseReturn | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);
    useEffect(() => { if (id) void purchaseApi.getReturn(Number(id)).then(setReturn).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load return.')); }, [id]);
    async function post() { if (!ret) return; setBusy(true); try { setReturn(await purchaseApi.postReturn(ret.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to post return.'); } finally { setBusy(false); } }
    if (!ret && !error) return <Loading label="Loading return" />;
    return <div className="space-y-5"><Header title={ret ? ret.returnNumber : 'Purchase return'} />{error ? <Alert message={error} /> : null}{ret ? <><div className="flex flex-wrap gap-2"><Button onClick={() => navigate('/purchase/returns')} variant="secondary">Back</Button>{ret.status === 'draft' ? <Button disabled={busy} onClick={() => void post()} variant="blue">Post return</Button> : null}{ret.status === 'draft' ? <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={`/purchase/returns/${ret.id}/edit`}>Edit</Link> : null}</div><Section title="Summary"><div className="grid gap-4 md:grid-cols-4"><Info label="Supplier" value={ret.supplierName ?? `#${ret.supplierId}`} /><Info label="GRN" value={ret.grnNumber ?? `#${ret.originalGrnId}`} /><Info label="Status" value={<Badge label={ret.status} />} /><Info label="Grand total" value={Number(ret.grandTotal).toLocaleString()} /></div></Section><Section title="Lines"><table className="w-full text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-3 py-2">Item</th><th className="px-3 py-2">Return qty</th><th className="px-3 py-2">Cost</th><th className="px-3 py-2">Total</th></tr></thead><tbody>{(ret.lines ?? []).map((line) => <tr className="border-b border-slate-100" key={line.id}><td className="px-3 py-3">{line.item_code} - {line.item_name}</td><td className="px-3 py-3">{line.return_qty}</td><td className="px-3 py-3">{line.unit_price}</td><td className="px-3 py-3">{line.line_total_with_tax}</td></tr>)}</tbody></table></Section>{ret.invoiceLinks?.length ? <Section title="Credit note"><div className="space-y-2">{ret.invoiceLinks.map((link) => <div className="rounded-lg border border-slate-100 p-3 text-sm" key={link.id}>{link.invoice_number} · {link.status} · {Number(link.grand_total).toLocaleString()}</div>)}</div></Section> : null}</> : null}</div>;
}

function Info({ label, value }: { label: string; value: ReactNode }) { return <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><div className="mt-1 text-sm font-semibold text-slate-800">{value}</div></div>; }
