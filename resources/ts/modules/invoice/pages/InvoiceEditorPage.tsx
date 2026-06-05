import { useEffect, useMemo, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { invoiceApi } from '../services/invoiceApi';
import type { InvoiceInput } from '../types/invoice.types';

export function InvoiceEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [form, setForm] = useState<InvoiceInput>({ businessContext: 'manual', documentType: 'invoice', invoiceDate: new Date().toISOString().slice(0, 10), ledgerDirection: 'receivable', lines: [{ chargeTotal: '0', description: '', discountTotal: '0', quantity: '1', taxTotal: '0', unitPrice: '0' }] });
    const total = useMemo(() => invoicePreview(form), [form]);
    useEffect(() => { if (mode === 'edit' && id) void invoiceApi.get(Number(id)).then((invoice) => {
        const adjustment = (type: string) => invoice.adjustments?.find((item) => item.adjustment_type === type);
        setForm({
            businessContext: invoice.businessContext,
            customerId: invoice.customerId ?? undefined,
            documentType: invoice.documentType,
            dueDate: invoice.dueDate ?? undefined,
            headerChargeTotal: String(adjustment('charge')?.amount ?? '0'),
            headerCreditAdjustmentTotal: String(adjustment('credit_adjustment')?.amount ?? '0'),
            headerDebitAdjustmentTotal: String(adjustment('debit_adjustment')?.amount ?? '0'),
            headerDiscountTotal: String(adjustment('discount')?.amount ?? '0'),
            headerTaxTotal: String(adjustment('tax')?.amount ?? '0'),
            invoiceDate: invoice.invoiceDate,
            invoiceNumber: invoice.invoiceNumber,
            ledgerDirection: invoice.ledgerDirection,
            lines: (invoice.lines ?? []).map((line) => ({ chargeTotal: String(line.charge_total ?? '0'), description: String(line.description ?? ''), discountTotal: String(line.discount_total ?? '0'), itemId: line.item_id ?? undefined, quantity: String(line.quantity ?? '1'), taxTotal: String(line.tax_total ?? '0'), unitPrice: String(line.unit_price ?? '0') })),
            roundingAdjustment: invoice.roundingAdjustment ?? '0',
            supplierId: invoice.supplierId ?? undefined,
        });
    }).catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load invoice.')); }, [id, mode]);

    async function submit(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError('');
        try { const saved = mode === 'edit' && id ? await invoiceApi.update(Number(id), form) : await invoiceApi.create(form); navigate(`/invoices/${saved.id}`, { replace: true }); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to save invoice.'); } finally { setSaving(false); }
    }

    return <form className="mx-auto max-w-5xl space-y-5" onSubmit={(event) => void submit(event)}><header><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Invoice</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{mode === 'create' ? 'Create invoice' : 'Edit invoice'}</h1></header>{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}<section className="grid gap-4 rounded-xl border bg-white p-5 shadow-sm sm:grid-cols-2"><Field label="Invoice number"><Input value={form.invoiceNumber || ''} onChange={(event) => setForm({ ...form, invoiceNumber: event.target.value })} placeholder="Auto if blank" /></Field><Field label="Invoice date"><Input required type="date" value={form.invoiceDate} onChange={(event) => setForm({ ...form, invoiceDate: event.target.value })} /></Field><Field label="Ledger"><select className="h-11 rounded-lg border px-3 text-sm" value={form.ledgerDirection} onChange={(event) => setForm({ ...form, ledgerDirection: event.target.value as InvoiceInput['ledgerDirection'] })}><option value="receivable">Receivable</option><option value="payable">Payable</option></select></Field><Field label={form.ledgerDirection === 'receivable' ? 'Customer ID' : 'Supplier ID'}><Input required type="number" value={form.ledgerDirection === 'receivable' ? form.customerId ?? '' : form.supplierId ?? ''} onChange={(event) => setForm(form.ledgerDirection === 'receivable' ? { ...form, customerId: Number(event.target.value), supplierId: undefined } : { ...form, supplierId: Number(event.target.value), customerId: undefined })} /></Field><Field label="Document type"><select className="h-11 rounded-lg border px-3 text-sm" value={form.documentType} onChange={(event) => setForm({ ...form, documentType: event.target.value as InvoiceInput['documentType'], balanceEffect: event.target.value === 'credit_adjustment' ? 'decrease' : 'increase' })}><option value="invoice">Invoice</option><option value="debit_adjustment">Debit note</option><option value="credit_adjustment">Credit note</option></select></Field><Field label="Original invoice ID"><Input type="number" value={form.originalInvoiceId ?? ''} onChange={(event) => setForm({ ...form, originalInvoiceId: event.target.value ? Number(event.target.value) : undefined })} /></Field></section><section className="rounded-xl border bg-white p-5 shadow-sm"><h2 className="font-bold">Lines</h2>{form.lines.map((line, index) => <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_90px_120px_110px_110px_110px]" key={index}><Input required placeholder="Description" value={line.description} onChange={(event) => updateLine(index, { description: event.target.value })} /><Input required min="0.0001" step="0.0001" type="number" value={line.quantity} onChange={(event) => updateLine(index, { quantity: event.target.value })} /><Input required min="0" step="0.0001" type="number" value={line.unitPrice} onChange={(event) => updateLine(index, { unitPrice: event.target.value })} /><Input min="0" step="0.0001" type="number" value={line.discountTotal ?? '0'} onChange={(event) => updateLine(index, { discountTotal: event.target.value })} /><Input min="0" step="0.0001" type="number" value={line.taxTotal} onChange={(event) => updateLine(index, { taxTotal: event.target.value })} /><Input min="0" step="0.0001" type="number" value={line.chargeTotal ?? '0'} onChange={(event) => updateLine(index, { chargeTotal: event.target.value })} /></div>)}<Button className="mt-3" onClick={() => setForm({ ...form, lines: [...form.lines, { chargeTotal: '0', description: '', discountTotal: '0', quantity: '1', taxTotal: '0', unitPrice: '0' }] })} type="button" variant="secondary">Add line</Button></section><section className="rounded-xl border bg-white p-5 shadow-sm"><h2 className="font-bold">Header Discount, Tax, Charges & Adjustments</h2><div className="mt-4 grid gap-4 md:grid-cols-3"><Field label="Header discount"><Input min="0" step="0.0001" type="number" value={form.headerDiscountTotal ?? '0'} onChange={(event) => setForm({ ...form, headerDiscountTotal: event.target.value })} /></Field><Field label="Header tax"><Input min="0" step="0.0001" type="number" value={form.headerTaxTotal ?? '0'} onChange={(event) => setForm({ ...form, headerTaxTotal: event.target.value })} /></Field><Field label="Header charge"><Input min="0" step="0.0001" type="number" value={form.headerChargeTotal ?? '0'} onChange={(event) => setForm({ ...form, headerChargeTotal: event.target.value })} /></Field><Field label="Debit adjustment add"><Input min="0" step="0.0001" type="number" value={form.headerDebitAdjustmentTotal ?? '0'} onChange={(event) => setForm({ ...form, headerDebitAdjustmentTotal: event.target.value })} /></Field><Field label="Credit adjustment deduct"><Input min="0" step="0.0001" type="number" value={form.headerCreditAdjustmentTotal ?? '0'} onChange={(event) => setForm({ ...form, headerCreditAdjustmentTotal: event.target.value })} /></Field><Field label="Rounding adjustment"><Input step="0.0001" type="number" value={form.roundingAdjustment ?? '0'} onChange={(event) => setForm({ ...form, roundingAdjustment: event.target.value })} /></Field></div></section><section className="rounded-xl border bg-white p-5 shadow-sm"><TotalsPreview totals={total} /></section><div className="flex justify-end gap-2"><Button disabled={saving} type="submit">{saving ? 'Saving...' : 'Save invoice'}</Button></div></form>;

    function updateLine(index: number, next: Partial<InvoiceInput['lines'][number]>) {
        setForm((current) => ({ ...current, lines: current.lines.map((line, candidate) => candidate === index ? { ...line, ...next } : line) }));
    }
}

function Field({ children, label }: { children: ReactNode; label: string }) { return <label className="grid gap-1 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }

function numberValue(value?: string) { return Number(value || 0); }
function invoicePreview(form: InvoiceInput) {
    const lines = form.lines.reduce((totals, line) => {
        const gross = numberValue(line.quantity) * numberValue(line.unitPrice);
        return {
            charges: totals.charges + numberValue(line.chargeTotal),
            gross: totals.gross + gross,
            lineDiscount: totals.lineDiscount + numberValue(line.discountTotal),
            tax: totals.tax + numberValue(line.taxTotal),
        };
    }, { charges: 0, gross: 0, lineDiscount: 0, tax: 0 });
    const headerDiscount = numberValue(form.headerDiscountTotal);
    const headerTax = numberValue(form.headerTaxTotal);
    const headerCharge = numberValue(form.headerChargeTotal);
    const debitAdjustment = numberValue(form.headerDebitAdjustmentTotal);
    const creditAdjustment = numberValue(form.headerCreditAdjustmentTotal);
    const rounding = numberValue(form.roundingAdjustment);
    const grandTotal = Math.max(0, lines.gross - lines.lineDiscount - headerDiscount + lines.tax + headerTax + lines.charges + headerCharge + debitAdjustment - creditAdjustment + rounding);

    return { ...lines, creditAdjustment, debitAdjustment, grandTotal, headerCharge, headerDiscount, headerTax, rounding };
}
function TotalsPreview({ totals }: { totals: ReturnType<typeof invoicePreview> }) {
    const rows = [
        ['Gross total', totals.gross],
        ['Line discount', -totals.lineDiscount],
        ['Header discount', -totals.headerDiscount],
        ['Line tax', totals.tax],
        ['Header tax', totals.headerTax],
        ['Line charges', totals.charges],
        ['Header charge', totals.headerCharge],
        ['Debit adjustment', totals.debitAdjustment],
        ['Credit adjustment', -totals.creditAdjustment],
        ['Rounding', totals.rounding],
    ];

    return <div className="space-y-2 text-sm">{rows.map(([label, amount]) => <div className="flex justify-between" key={label as string}><span className="text-slate-500">{label}</span><span className="font-semibold text-slate-900">{Number(amount).toLocaleString()}</span></div>)}<div className="mt-3 flex justify-between border-t border-slate-200 pt-3"><span className="font-bold">Grand total</span><span className="text-2xl font-bold">{totals.grandTotal.toLocaleString()}</span></div></div>;
}
