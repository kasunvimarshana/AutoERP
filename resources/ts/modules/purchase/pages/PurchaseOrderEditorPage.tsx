import { FormEvent, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { CreditUtilization, FormSection, MoneyDisplay, PageHeader, SummaryCard, SummaryRow, TotalsSummaryCard } from '../../../shared/components/erp/ErpUi';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseHeaderTotalsInput, PurchaseLineInput, PurchaseLookup, PurchaseOrderInput } from '../types/purchase.types';

const blankLine: PurchaseLineInput = { itemId: 0, receivedQty: '1', taxAmount: '0', unitPrice: '0', uomId: 0 };

function numeric(value?: string | null) { return Number(value || 0); }
function discountAmount(gross: number, type?: string, value?: string, explicit?: string) {
    if (type === 'percentage') return gross * (numeric(value) / 100);
    if (type === 'fixed') return Math.min(gross, numeric(value));
    return Math.min(gross, numeric(explicit));
}
export function purchasePreview(value: { lines: PurchaseLineInput[] } & PurchaseHeaderTotalsInput) {
    const lineTotals = value.lines.reduce((totals, line) => {
        const quantity = numeric(line.receivedQty ?? line.returnQty);
        const gross = quantity * numeric(line.unitPrice);
        const discount = discountAmount(gross, line.discountType, line.discountValue, line.discountAmount);
        return { lineDiscount: totals.lineDiscount + discount, lineTax: totals.lineTax + numeric(line.taxAmount), subtotal: totals.subtotal + gross };
    }, { lineDiscount: 0, lineTax: 0, subtotal: 0 });
    const headerBase = Math.max(0, lineTotals.subtotal - lineTotals.lineDiscount);
    const headerDiscount = discountAmount(headerBase, value.headerDiscountType, value.headerDiscountValue, value.headerDiscountAmount);
    const headerTax = numeric(value.headerTaxAmount);
    const additions = numeric(value.headerChargeTotal ?? value.debitNoteTotal ?? value.headerDebitAdjustmentTotal);
    const deductions = numeric(value.creditNoteTotal ?? value.headerCreditAdjustmentTotal);
    const grandTotal = Math.max(0, lineTotals.subtotal - lineTotals.lineDiscount - headerDiscount + lineTotals.lineTax + headerTax + additions - deductions);

    return { ...lineTotals, additions, deductions, grandTotal, headerDiscount, headerTax };
}

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
    useEffect(() => { if (mode === 'edit' && id) void purchaseApi.getOrder(Number(id)).then((order) => setValue({ creditNoteTotal: order.creditNoteTotal, debitNoteTotal: order.debitNoteTotal, expectedDate: order.expectedDate ?? undefined, headerDiscountAmount: order.headerDiscountAmount, headerDiscountType: order.headerDiscountType ?? '', headerDiscountValue: order.headerDiscountValue ?? undefined, headerTaxAmount: order.headerTaxAmount, lines: (order.lines ?? []).map((line) => ({ description: line.description ?? undefined, discountAmount: line.discount_amount, itemId: line.item_id, receivedQty: line.received_qty ?? line.ordered_qty ?? '1', taxAmount: line.tax_amount, unitPrice: line.unit_price, uomId: line.uom_id })), notes: order.notes ?? undefined, orderDate: order.orderDate, poNumber: order.poNumber, reference: order.reference ?? undefined, supplierId: order.supplierId, warehouseId: order.warehouseId })).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load purchase order.')); }, [id, mode]);
    const total = useMemo(() => purchasePreview(value), [value]);
    const selectedSupplier = suppliers.find((supplier) => supplier.id === value.supplierId);
    const selectedWarehouse = warehouses.find((warehouse) => warehouse.id === value.warehouseId);

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
        <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
            <div className="space-y-5">
                <Section title="Basic Information"><div className="grid gap-4 md:grid-cols-3"><Field label="PO number"><Input value={value.poNumber ?? ''} onChange={(event) => setValue({ ...value, poNumber: event.target.value })} placeholder="Auto if empty" /></Field><Field label="Order date"><Input type="date" value={value.orderDate} onChange={(event) => setValue({ ...value, orderDate: event.target.value })} /></Field><Field label="Expected date"><Input type="date" value={value.expectedDate ?? ''} onChange={(event) => setValue({ ...value, expectedDate: event.target.value })} /></Field></div></Section>
                <Section title="Supplier & Warehouse"><div className="grid gap-4 md:grid-cols-2"><Lookup label="Supplier" options={suppliers} value={value.supplierId} onChange={(supplierId) => setValue({ ...value, supplierId })} /><Lookup label="Warehouse" options={warehouses} value={value.warehouseId} onChange={(warehouseId) => setValue({ ...value, warehouseId })} /></div></Section>
                <Section title="Item Lines"><Lines items={items} lines={value.lines} onAdd={() => setValue({ ...value, lines: [...value.lines, blankLine] })} onChange={updateLine} onItem={pickItem} onRemove={(index) => setValue({ ...value, lines: value.lines.filter((_, lineIndex) => lineIndex !== index) })} uoms={uoms} /></Section>
                <Section title="Header Discount, Tax, Charges & Adjustments"><HeaderTotalsFields value={value} onChange={(next) => setValue({ ...value, ...next })} /></Section>
                <Section title="Notes"><textarea className="min-h-24 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm" value={value.notes ?? ''} onChange={(event) => setValue({ ...value, notes: event.target.value })} /></Section>
            </div>
            <div className="space-y-5 xl:sticky xl:top-24 xl:self-start">
                <SummaryCard title="Purchase Summary">
                    <SummaryRow label="Supplier" value={selectedSupplier?.name ?? 'Not selected'} />
                    <SummaryRow label="Warehouse" value={selectedWarehouse?.name ?? 'Not selected'} />
                    <SummaryRow label="Lines" value={value.lines.length} />
                    <SummaryRow label="Expected date" value={value.expectedDate || 'Not set'} />
                    {selectedSupplier ? <CreditUtilization availableCredit={Number(selectedSupplier.credit_limit || 0) - Number(selectedSupplier.outstanding_balance || 0)} creditLimit={selectedSupplier.credit_limit ?? 0} currentBalance={selectedSupplier.outstanding_balance ?? 0} /> : null}
                </SummaryCard>
                <SummaryCard title="Totals">
                    <SummaryRow label="Subtotal" value={<MoneyDisplay value={total.subtotal} />} />
                    <SummaryRow label="Discounts" value={<MoneyDisplay value={total.lineDiscount + total.headerDiscount} />} />
                    <SummaryRow label="Tax" value={<MoneyDisplay value={total.lineTax + total.headerTax} />} />
                    <SummaryRow label="Adjustments" value={<MoneyDisplay value={total.additions - total.deductions} />} />
                    <div className="border-t border-slate-100 pt-4"><SummaryRow label="Grand total" value={<MoneyDisplay className="text-lg font-black" value={total.grandTotal} />} /></div>
                </SummaryCard>
                <div className="flex justify-end gap-3"><Button onClick={() => navigate('/purchase/orders')} variant="secondary">Cancel</Button><Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving' : 'Save purchase order'}</Button></div>
            </div>
        </div>
    </form>;
}

export function Header({ title }: { title: string }) { return <PageHeader eyebrow="Operations" subtitle="Supplier, warehouse, line, adjustment, and total information." title={title} />; }
export function Alert({ message }: { message: string }) { return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{message}</div>; }
export function Section({ children, title }: { children: ReactNode; title: string }) { return <FormSection title={title}>{children}</FormSection>; }
export function Field({ children, label }: { children: ReactNode; label: string }) { return <label className="space-y-2 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }
export function Lookup({ label, onChange, options, value }: { label: string; onChange: (id: number) => void; options: PurchaseLookup[]; value?: number }) { return <Field label={label}><select className="erp-select" value={value || ''} onChange={(event) => onChange(Number(event.target.value))}><option value="">Select {label.toLowerCase()}</option>{options.map((item) => <option key={item.id} value={item.id}>{item.code ? `${item.code} - ` : ''}{item.name}</option>)}</select></Field>; }
export function HeaderTotalsFields({ onChange, value }: { onChange: (next: Partial<PurchaseHeaderTotalsInput>) => void; value: PurchaseHeaderTotalsInput }) {
    return <div className="grid gap-4 md:grid-cols-3">
        <Field label="Header discount type"><select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.headerDiscountType ?? ''} onChange={(event) => onChange({ headerDiscountType: event.target.value as PurchaseHeaderTotalsInput['headerDiscountType'] })}><option value="">Amount</option><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></Field>
        <Field label="Header discount value"><Input inputMode="decimal" value={value.headerDiscountValue ?? ''} onChange={(event) => onChange({ headerDiscountValue: event.target.value })} placeholder="Used for percentage/fixed" /></Field>
        <Field label="Header discount amount"><Input inputMode="decimal" value={value.headerDiscountAmount ?? '0'} onChange={(event) => onChange({ headerDiscountAmount: event.target.value })} /></Field>
        <Field label="Header tax"><Input inputMode="decimal" value={value.headerTaxAmount ?? '0'} onChange={(event) => onChange({ headerTaxAmount: event.target.value })} /></Field>
        <Field label="Header charge / debit add"><Input inputMode="decimal" value={value.headerChargeTotal ?? value.debitNoteTotal ?? '0'} onChange={(event) => onChange({ debitNoteTotal: event.target.value, headerChargeTotal: event.target.value })} /></Field>
        <Field label="Credit adjustment deduct"><Input inputMode="decimal" value={value.creditNoteTotal ?? '0'} onChange={(event) => onChange({ creditNoteTotal: event.target.value, headerCreditAdjustmentTotal: event.target.value })} /></Field>
    </div>;
}
export function TotalsPreview({ totals }: { totals: ReturnType<typeof purchasePreview> }) {
    const rows = [
        ['Line subtotal', totals.subtotal],
        ['Line discount', -totals.lineDiscount],
        ['Header discount', -totals.headerDiscount],
        ['Line tax', totals.lineTax],
        ['Header tax', totals.headerTax],
        ['Header charge / debit add', totals.additions],
        ['Credit adjustment deduct', -totals.deductions],
    ];

    return <TotalsSummaryCard grandTotal={totals.grandTotal} rows={rows.map(([label, amount]) => ({ label: String(label), value: Number(amount) }))} />;
}
function Lines({ items, lines, onAdd, onChange, onItem, onRemove, uoms }: { items: PurchaseLookup[]; lines: PurchaseLineInput[]; onAdd: () => void; onChange: (index: number, line: Partial<PurchaseLineInput>) => void; onItem: (index: number, itemId: number) => void; onRemove: (index: number) => void; uoms: PurchaseLookup[] }) { return <div className="space-y-3">{lines.map((line, index) => <div className="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[1.4fr_1fr_90px_110px_110px_110px_70px]" key={index}><Lookup label="Item" options={items} value={line.itemId} onChange={(id) => onItem(index, id)} /><Lookup label="UOM" options={uoms} value={line.uomId} onChange={(uomId) => onChange(index, { uomId })} /><Field label="Qty"><Input inputMode="decimal" value={line.receivedQty ?? line.returnQty ?? '1'} onChange={(event) => onChange(index, { receivedQty: event.target.value, returnQty: event.target.value })} /></Field><Field label="Unit cost"><Input inputMode="decimal" value={line.unitPrice} onChange={(event) => onChange(index, { unitPrice: event.target.value })} /></Field><Field label="Line discount"><Input inputMode="decimal" value={line.discountAmount ?? '0'} onChange={(event) => onChange(index, { discountAmount: event.target.value })} /></Field><Field label="Line tax"><Input inputMode="decimal" value={line.taxAmount ?? '0'} onChange={(event) => onChange(index, { taxAmount: event.target.value })} /></Field><button className="mt-7 rounded-md px-2 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50" onClick={() => onRemove(index)} type="button">Remove</button></div>)}<Button onClick={onAdd} variant="secondary">Add line</Button></div>; }
