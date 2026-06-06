import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { FormSection, PageHeader } from '../../../shared/components/erp/ErpUi';
import { paymentApi } from '../services/paymentApi';
import type { OutstandingInvoiceLookup, PaymentInput } from '../types/payment.types';

export function PaymentCreatePage() {
    const navigate = useNavigate();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [form, setForm] = useState<PaymentInput>({ amount: '0', direction: 'inbound', partyId: 0, partyType: 'customer', paymentDate: new Date().toISOString().slice(0, 10), paymentMethodId: 1, allocations: [] });
    const [invoices, setInvoices] = useState<OutstandingInvoiceLookup[]>([]);
    const [invoiceLoading, setInvoiceLoading] = useState(false);
    const [selectedInvoiceId, setSelectedInvoiceId] = useState<number | ''>('');
    useEffect(() => {
        if (!form.partyId) {
            setInvoices([]);
            setSelectedInvoiceId('');
            setForm((current) => ({ ...current, allocations: [] }));
            return;
        }
        setInvoiceLoading(true);
        void paymentApi.lookup('outstanding-invoices', { direction: form.direction, partyId: form.partyId, partyType: form.partyType })
            .then((records) => {
                setInvoices(records);
                setSelectedInvoiceId('');
                setForm((current) => ({ ...current, allocations: [] }));
            })
            .catch(() => setInvoices([]))
            .finally(() => setInvoiceLoading(false));
    }, [form.direction, form.partyId, form.partyType]);
    function selectInvoice(invoiceId: number | '') {
        setSelectedInvoiceId(invoiceId);
        setForm({ ...form, allocations: invoiceId ? [{ allocatedAmount: form.amount, invoiceId }] : [] });
    }
    function updateAmount(amount: string) {
        setForm({ ...form, amount, allocations: selectedInvoiceId ? [{ allocatedAmount: amount, invoiceId: selectedInvoiceId }] : [] });
    }
    async function submit(event: FormEvent) { event.preventDefault(); setSaving(true); setError(''); try { const saved = await paymentApi.create(form); navigate(`/payments/${saved.id}`, { replace: true }); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to save payment.'); } finally { setSaving(false); } }
    return <form className="mx-auto max-w-3xl space-y-5" onSubmit={(event) => void submit(event)}><PageHeader eyebrow="Finance" subtitle="Select a party, load outstanding invoices, then enter the payment amount. Backend balances remain the source of truth." title="Create payment" />{error ? <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div> : null}<FormSection title="Basic information"><div className="grid gap-4 sm:grid-cols-2"><Field label="Payment number"><Input value={form.paymentNumber || ''} onChange={(event) => setForm({ ...form, paymentNumber: event.target.value })} placeholder="Auto if blank" /></Field><Field label="Date"><Input required type="date" value={form.paymentDate} onChange={(event) => setForm({ ...form, paymentDate: event.target.value })} /></Field><Field label="Direction"><select className="erp-select" value={form.direction} onChange={(event) => setForm({ ...form, direction: event.target.value as PaymentInput['direction'], partyType: event.target.value === 'outbound' ? 'supplier' : 'customer' })}><option value="inbound">Inbound customer receipt</option><option value="outbound">Outbound supplier payment</option></select></Field><Field label={`${form.partyType} ID`}><Input required min="1" type="number" value={form.partyId || ''} onChange={(event) => setForm({ ...form, partyId: Number(event.target.value) })} /></Field><Field label="Payment method ID"><Input required min="1" type="number" value={form.paymentMethodId} onChange={(event) => setForm({ ...form, paymentMethodId: Number(event.target.value) })} /></Field><Field label="Payment amount"><Input required min="0.0001" step="0.0001" type="number" value={form.amount} onChange={(event) => updateAmount(event.target.value)} /></Field></div></FormSection><FormSection description="Choose an outstanding invoice loaded from the backend. Leave blank to record the unallocated balance as an advance when supported." title="Source invoice allocation"><div className="grid gap-4"><Field label={invoiceLoading ? 'Loading invoices...' : 'Outstanding invoice'}><select className="erp-select" value={selectedInvoiceId} onChange={(event) => selectInvoice(event.target.value ? Number(event.target.value) : '')}><option value="">No invoice selected - keep as advance</option>{invoices.map((invoice) => <option key={invoice.id} value={invoice.id}>{invoice.code} · balance {Number(invoice.balance_total).toLocaleString()}</option>)}</select></Field>{selectedInvoiceId ? <InvoiceBalance invoice={invoices.find((invoice) => invoice.id === selectedInvoiceId)} /> : <p className="text-sm text-slate-500">Select an invoice to allocate this payment. The backend will reject allocations above the current balance.</p>}</div></FormSection><div className="flex justify-end"><Button disabled={saving} type="submit">{saving ? 'Saving...' : 'Save payment'}</Button></div></form>;
}

function Field({ children, label }: { children: ReactNode; label: string }) { return <label className="grid gap-1 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }
function InvoiceBalance({ invoice }: { invoice?: OutstandingInvoiceLookup }) { if (!invoice) return null; return <div className="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm sm:grid-cols-3"><Info label="Invoice total" value={Number(invoice.grand_total).toLocaleString()} /><Info label="Paid/settled" value={Number(invoice.settled_total).toLocaleString()} /><Info label="Balance due" value={Number(invoice.balance_total).toLocaleString()} /></div>; }
function Info({ label, value }: { label: string; value: ReactNode }) { return <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><div className="mt-1 font-semibold text-slate-800">{value}</div></div>; }
