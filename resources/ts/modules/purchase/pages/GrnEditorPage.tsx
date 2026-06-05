import { FormEvent, useEffect, useState, type ReactNode } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { purchaseApi } from '../services/purchaseApi';
import type { GrnInput, PurchaseLineInput, PurchaseLookup } from '../types/purchase.types';
import { Alert, Header, Lookup, Section } from './PurchaseOrderEditorPage';

const today = new Date().toISOString().slice(0, 10);

export function GrnEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const { id } = useParams();
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const [items, setItems] = useState<PurchaseLookup[]>([]);
    const [openOrders, setOpenOrders] = useState<PurchaseLookup[]>([]);
    const [uoms, setUoms] = useState<PurchaseLookup[]>([]);
    const [warehouses, setWarehouses] = useState<PurchaseLookup[]>([]);
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);
    const [value, setValue] = useState<GrnInput>({ lines: [], purchaseOrderId: Number(params.get('purchaseOrderId')) || undefined, receivedDate: today });
    useEffect(() => { void Promise.all([purchaseApi.lookup('items'), purchaseApi.lookup('uoms'), purchaseApi.lookup('warehouses'), purchaseApi.lookup('open-purchase-orders')]).then(([i, u, w, o]) => { setItems(i); setUoms(u); setWarehouses(w); setOpenOrders(o); }); }, []);
    useEffect(() => { const poId = Number(params.get('purchaseOrderId')); if (mode === 'create' && poId) void loadPurchaseOrder(poId); }, [mode, params]);
    useEffect(() => { if (mode === 'edit' && id) void purchaseApi.getGrn(Number(id)).then((grn) => setValue({ grnNumber: grn.grnNumber, lines: (grn.lines ?? []).map((line) => ({ acceptedQty: line.accepted_qty, itemId: line.item_id, purchaseOrderLineId: line.purchase_order_line_id ?? undefined, receivedQty: line.received_qty, taxAmount: line.tax_amount, unitPrice: line.unit_price, uomId: line.uom_id, warehouseId: line.warehouse_id ?? grn.warehouseId })), notes: grn.notes ?? undefined, purchaseOrderId: grn.purchaseOrderId ?? undefined, receivedDate: grn.receivedDate, reference: grn.reference ?? undefined, supplierId: grn.supplierId, warehouseId: grn.warehouseId })).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load GRN.')); }, [id, mode]);
    function updateLine(index: number, next: Partial<PurchaseLineInput>) { setValue((current) => ({ ...current, lines: current.lines.map((line, lineIndex) => lineIndex === index ? { ...line, ...next } : line) })); }
    async function loadPurchaseOrder(poId: number) {
        const order = await purchaseApi.getOrder(poId);
        setValue((current) => ({ ...current, purchaseOrderId: poId, supplierId: order.supplierId, warehouseId: order.warehouseId, lines: (order.lines ?? []).filter((line) => Number(line.ordered_qty) > Number(line.received_qty ?? 0)).map((line) => ({ itemId: line.item_id, purchaseOrderLineId: line.id, receivedQty: String(Number(line.ordered_qty) - Number(line.received_qty ?? 0)), unitPrice: line.unit_price, uomId: line.uom_id, warehouseId: order.warehouseId })) }));
    }
    async function submit(event: FormEvent) { event.preventDefault(); setSaving(true); setError(''); try { const saved = mode === 'edit' && id ? await purchaseApi.updateGrn(Number(id), value) : await purchaseApi.createGrn(value); navigate(`/purchase/grns/${saved.id}`); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to save GRN.'); } finally { setSaving(false); } }
    return <form className="space-y-5" onSubmit={(event) => void submit(event)}><Header title={mode === 'edit' ? 'Edit GRN' : 'Create GRN'} />{error ? <Alert message={error} /> : null}<Section title="Basic Information"><div className="grid gap-4 md:grid-cols-4"><Field label="GRN number"><Input value={value.grnNumber ?? ''} onChange={(event) => setValue({ ...value, grnNumber: event.target.value })} placeholder="Auto if empty" /></Field><Field label="Received date"><Input type="date" value={value.receivedDate} onChange={(event) => setValue({ ...value, receivedDate: event.target.value })} /></Field>{mode === 'create' ? <Lookup label="Purchase order" options={openOrders} value={value.purchaseOrderId} onChange={(poId) => void loadPurchaseOrder(poId)} /> : <Field label="Purchase order"><Input readOnly value={value.purchaseOrderId ?? ''} /></Field>}<Lookup label="Warehouse" options={warehouses} value={value.warehouseId} onChange={(warehouseId) => setValue({ ...value, warehouseId })} /></div></Section><Section title="Item Lines"><div className="space-y-3">{value.lines.map((line, index) => <div className="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-5" key={index}><Lookup label="Item" options={items} value={line.itemId} onChange={(itemId) => updateLine(index, { itemId })} /><Lookup label="UOM" options={uoms} value={line.uomId} onChange={(uomId) => updateLine(index, { uomId })} /><Field label="Received qty"><Input value={line.receivedQty ?? '1'} onChange={(event) => updateLine(index, { acceptedQty: event.target.value, receivedQty: event.target.value })} /></Field><Field label="Unit cost"><Input readOnly value={line.unitPrice} /></Field><Button onClick={() => setValue({ ...value, lines: value.lines.filter((_, lineIndex) => lineIndex !== index) })} variant="secondary">Skip</Button></div>)}{!value.lines.length ? <p className="text-sm text-slate-500">Select an open purchase order to load receivable lines.</p> : null}</div></Section><Section title="Notes"><textarea className="min-h-24 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm" value={value.notes ?? ''} onChange={(event) => setValue({ ...value, notes: event.target.value })} /></Section><div className="flex justify-end gap-3"><Button onClick={() => navigate('/purchase/grns')} variant="secondary">Cancel</Button><Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving' : 'Save GRN'}</Button></div></form>;
}

function Field({ children, label }: { children: ReactNode; label: string }) { return <label className="space-y-2 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }
