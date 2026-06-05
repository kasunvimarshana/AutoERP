import { FormEvent, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseLineInput, PurchaseLookup, PurchaseOrderInput } from '../types/purchase.types';

const blankLine: PurchaseLineInput = { itemId: 0, receivedQty: '1', taxAmount: '0', unitPrice: '0', uomId: 0 };

export function PurchaseOrderEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const [suppliers, setSuppliers] = useState<PurchaseLookup[]>([]);
    const [items, setItems] = useState<PurchaseLookup[]>([]);
    const [uoms, setUoms] = useState<PurchaseLookup[]>([]);
    const [warehouses, setWarehouses] = useState<PurchaseLookup[]>([]);
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);
    const [value, setValue] = useState<PurchaseOrderInput>({ lines: [blankLine], orderDate: new Date().toISOString().slice(0, 10), supplierId: 0, warehouseId: 0 });

    useEffect(() => { void Promise.all([purchaseApi.lookup('suppliers'), purchaseApi.lookup('items'), purchaseApi.lookup('uoms'), purchaseApi.lookup('warehouses')]).then(([s, i, u, w]) => { setSuppliers(s); setItems(i); setUoms(u); setWarehouses(w); }); }, []);
    useEffect(() => { if (mode === 'edit' && id) void purchaseApi.getOrder(Number(id)).then((order) => setValue({ expectedDate: order.expectedDate ?? undefined, lines: (order.lines ?? []).map((line) => ({ description: line.description ?? undefined, itemId: line.item_id, receivedQty: line.received_qty ?? '1', taxAmount: line.tax_amount, unitPrice: line.unit_price, uomId: line.uom_id })), notes: order.notes ?? undefined, orderDate: order.orderDate, poNumber: order.poNumber, reference: order.reference ?? undefined, supplierId: order.supplierId, warehouseId: order.warehouseId })).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load purchase order.')); }, [id, mode]);
    const total = useMemo(() => value.lines.reduce((sum, line) => sum + Number(line.receivedQty || 0) * Number(line.unitPrice || 0) + Number(line.taxAmount || 0) - Number(line.discountAmount || 0), 0), [value.lines]);

    function updateLine(index: number, next: Partial<PurchaseLineInput>) {
        setValue((current) => ({ ...current, lines: current.lines.map((line, lineIndex) => lineIndex === index ? { ...line, ...next } : line) }));
    }
    function pickItem(index: number, itemId: number) {
        const item = items.find((entry) => entry.id === itemId);
        updateLine(index, { itemId, unitPrice: item?.cost_price ?? '0', uomId: item?.purchase_uom_id ?? item?.base_uom_id ?? 0 });
    }
    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true); setError('');
        try {
            const saved = mode === 'edit' && id ? await purchaseApi.updateOrder(Number(id), value) : await purchaseApi.createOrder(value);
            navigate(`/purchase/orders/${saved.id}`);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to save purchase order.');
        } finally { setSaving(false); }
    }

    return <form className="space-y-5" onSubmit={(event) => void submit(event)}><Header title={mode === 'edit' ? 'Edit purchase order' : 'Create purchase order'} />
        {error ? <Alert message={error} /> : null}
        <Section title="Basic Information"><div className="grid gap-4 md:grid-cols-3"><Field label="PO number"><Input value={value.poNumber ?? ''} onChange={(event) => setValue({ ...value, poNumber: event.target.value })} placeholder="Auto if empty" /></Field><Field label="Order date"><Input type="date" value={value.orderDate} onChange={(event) => setValue({ ...value, orderDate: event.target.value })} /></Field><Field label="Expected date"><Input type="date" value={value.expectedDate ?? ''} onChange={(event) => setValue({ ...value, expectedDate: event.target.value })} /></Field></div></Section>
        <Section title="Supplier & Warehouse"><div className="grid gap-4 md:grid-cols-2"><Lookup label="Supplier" options={suppliers} value={value.supplierId} onChange={(supplierId) => setValue({ ...value, supplierId })} /><Lookup label="Warehouse" options={warehouses} value={value.warehouseId} onChange={(warehouseId) => setValue({ ...value, warehouseId })} /></div></Section>
        <Section title="Item Lines"><Lines items={items} lines={value.lines} onAdd={() => setValue({ ...value, lines: [...value.lines, blankLine] })} onChange={updateLine} onItem={pickItem} onRemove={(index) => setValue({ ...value, lines: value.lines.filter((_, lineIndex) => lineIndex !== index) })} uoms={uoms} /></Section>
        <Section title="Totals"><p className="text-2xl font-bold text-slate-950">{total.toLocaleString()}</p></Section>
        <Section title="Notes"><textarea className="min-h-24 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm" value={value.notes ?? ''} onChange={(event) => setValue({ ...value, notes: event.target.value })} /></Section>
        <div className="flex justify-end gap-3"><Button onClick={() => navigate('/purchase/orders')} variant="secondary">Cancel</Button><Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving' : 'Save purchase order'}</Button></div>
    </form>;
}

export function Header({ title }: { title: string }) { return <header><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Procurement</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{title}</h1></header>; }
export function Alert({ message }: { message: string }) { return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{message}</div>; }
export function Section({ children, title }: { children: ReactNode; title: string }) { return <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="text-base font-bold text-slate-950">{title}</h2><div className="mt-4">{children}</div></section>; }
export function Field({ children, label }: { children: ReactNode; label: string }) { return <label className="space-y-2 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }
export function Lookup({ label, onChange, options, value }: { label: string; onChange: (id: number) => void; options: PurchaseLookup[]; value?: number }) { return <Field label={label}><select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value || ''} onChange={(event) => onChange(Number(event.target.value))}><option value="">Select {label.toLowerCase()}</option>{options.map((item) => <option key={item.id} value={item.id}>{item.code ? `${item.code} - ` : ''}{item.name}</option>)}</select></Field>; }
function Lines({ items, lines, onAdd, onChange, onItem, onRemove, uoms }: { items: PurchaseLookup[]; lines: PurchaseLineInput[]; onAdd: () => void; onChange: (index: number, line: Partial<PurchaseLineInput>) => void; onItem: (index: number, itemId: number) => void; onRemove: (index: number) => void; uoms: PurchaseLookup[] }) { return <div className="space-y-3">{lines.map((line, index) => <div className="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[1.4fr_1fr_100px_120px_120px_80px]" key={index}><Lookup label="Item" options={items} value={line.itemId} onChange={(id) => onItem(index, id)} /><Lookup label="UOM" options={uoms} value={line.uomId} onChange={(uomId) => onChange(index, { uomId })} /><Field label="Qty"><Input inputMode="decimal" value={line.receivedQty ?? line.returnQty ?? '1'} onChange={(event) => onChange(index, { receivedQty: event.target.value, returnQty: event.target.value })} /></Field><Field label="Unit cost"><Input inputMode="decimal" value={line.unitPrice} onChange={(event) => onChange(index, { unitPrice: event.target.value })} /></Field><Field label="Tax"><Input inputMode="decimal" value={line.taxAmount ?? '0'} onChange={(event) => onChange(index, { taxAmount: event.target.value })} /></Field><button className="mt-7 rounded-md px-2 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50" onClick={() => onRemove(index)} type="button">Remove</button></div>)}<Button onClick={onAdd} variant="secondary">Add line</Button></div>; }
