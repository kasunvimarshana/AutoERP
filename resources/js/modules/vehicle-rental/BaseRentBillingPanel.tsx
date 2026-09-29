import { useEffect, useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { InvoiceStatus } from '@/modules/invoice/invoiceTypes';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { AgreementKind, type Agreement } from './agreements';
import { billBaseRent, loadBaseCharges, reissueBaseRent, voidBaseCharge, type BaseCharge, type CreatedRentalInvoice } from './baseRentBillingApi';

enum ChargeAction { Reissue = 'reissue', Void = 'void' }
const RELEASED_INVOICE_STATES = new Set<string>([InvoiceStatus.Cancelled, InvoiceStatus.Void, InvoiceStatus.Reversed]);
export function BaseRentBillingPanel({ kind, agreement }: { kind: AgreementKind; agreement: Agreement }) {
    const [from, setFrom] = useState(agreement.starts_on);
    const [until, setUntil] = useState('');
    const [invoiceDate, setInvoiceDate] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [exchangeRate, setExchangeRate] = useState('');
    const [accepted, setAccepted] = useState(false);
    const [charges, setCharges] = useState<BaseCharge[]>([]);
    const [selected, setSelected] = useState<BaseCharge | null>(null);
    const [action, setAction] = useState(ChargeAction.Reissue);
    const [reason, setReason] = useState('');
    const [created, setCreated] = useState<CreatedRentalInvoice | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const inFlight = useRef(false);
    useEffect(() => {
        const controller = new AbortController();
        loadBaseCharges(kind, agreement, page, controller.signal).then(value => {
            if (!controller.signal.aborted) { setCharges(value.data); setLastPage(value.last_page); }
        }).catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); });
        return () => controller.abort();
    }, [kind, agreement, page, revision]);
    async function submit(event: FormEvent) {
        event.preventDefault();
        if (inFlight.current || !accepted) return;
        inFlight.current = true; setBusy(true); setError(null); setCreated(null);
        const document = { invoice_date: invoiceDate, due_date: dueDate || null, exchange_rate: exchangeRate };
        try {
            if (selected && action === ChargeAction.Void) {
                await voidBaseCharge(kind, agreement, selected, reason);
                setRevision(value => value + 1); setAccepted(false); setSelected(null); setReason(''); return;
            }
            const invoice = selected ? await reissueBaseRent(kind, agreement, selected, document) : await billBaseRent(kind, agreement, { from, until }, document);
            setCreated(invoice); setRevision(value => value + 1); setAccepted(false); setSelected(null);
        } catch (failure) { setError(toApiError(failure)); }
        finally { inFlight.current = false; setBusy(false); }
    }
    return <details className="rounded border p-3"><summary className="cursor-pointer font-medium">Bill base rent</summary>
        <p className="my-3 text-sm">Create a {kind === AgreementKind.Customer ? 'customer invoice' : 'supplier payable'} draft from the recorded base rate and actual-calendar policy. Monthly partial periods use actual anniversary-cycle days. This bills base rent only; mileage, extras and deductions are separate. Tax uses the configured Tax rules. Review, approve and post in Invoice.</p>
        <form onSubmit={submit} className="space-y-3">
            {selected ? <p>{action === ChargeAction.Void ? 'Void original charge:' : 'Reissue original charge:'} {selected.from} – {selected.until} · {selected.currency} {selected.amount} <Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(null); setAccepted(false); }}>Cancel selection</Button></p> : <>
                <Input label="Charge from" type="date" required min={agreement.starts_on} value={from} disabled={busy} onChange={e => { setFrom(e.target.value); setAccepted(false); }} />
                <Input label="Charge through" type="date" required min={from} max={agreement.ends_on ?? undefined} value={until} disabled={busy} onChange={e => { setUntil(e.target.value); setAccepted(false); }} />
            </>}
            {selected && action === ChargeAction.Void ? <Input label="Void reason" required value={reason} disabled={busy} onChange={e => setReason(e.target.value)} /> : <>
            <Input label="Invoice date" type="date" required value={invoiceDate} disabled={busy} onChange={e => setInvoiceDate(e.target.value)} />
            <Input label="Due date (optional)" type="date" min={invoiceDate} value={dueDate} disabled={busy} onChange={e => setDueDate(e.target.value)} />
            <Input label="Exchange rate to base currency" required inputMode="decimal" value={exchangeRate} disabled={busy} onChange={e => setExchangeRate(e.target.value)} />
            <p className="text-sm">Enter the verified accounting exchange rate; use one when both currencies are the same.</p></>}
            <label className="flex gap-2"><input type="checkbox" checked={accepted} disabled={busy} onChange={e => setAccepted(e.target.checked)} />{selected ? (action === ChargeAction.Void ? 'Void this charge after releasing its invoices; retain its calculation history.' : 'Reissue this unchanged base charge using the document details above.') : 'Apply the stated actual-calendar policy to this base charge.'}</label>
            <ErrorAlert error={error} inline />
            <Button type="submit" loading={busy} disabled={!accepted || (selected && action === ChargeAction.Void ? !reason.trim() : (!invoiceDate || !exchangeRate || (!selected && (!from || !until))))}>{selected && action === ChargeAction.Void ? 'Void charge' : 'Create invoice draft'}</Button>
        </form>
        {created && <p role="status" className="mt-3">Created <Link className="underline" to={`/invoices/${created.id}`}>{created.invoice_number}</Link> · total {created.grand_total}</p>}
        <section aria-label="Recorded base charges" className="mt-4"><h3 className="font-medium">Recorded base charges</h3>
            {charges.map(charge => <div key={charge.id} className="my-2 border-t py-2"><p>{charge.from} – {charge.until} · {charge.currency} {charge.amount}</p>
                {charge.invoices.map(invoice => <p key={invoice.id}><Link className="underline" to={`/invoices/${invoice.id}`}>{invoice.number}</Link> · {invoice.status}</p>)}
                {charge.voided_at && <p>Voided: {charge.void_reason}</p>}
                {!charge.voided_at && charge.invoices.length > 0 && charge.invoices.every(invoice => RELEASED_INVOICE_STATES.has(invoice.status)) && <div className="flex gap-2"><Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(charge); setAction(ChargeAction.Reissue); setAccepted(false); setCreated(null); }}>Reissue charge</Button><Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(charge); setAction(ChargeAction.Void); setReason(''); setAccepted(false); setCreated(null); }}>Void charge</Button></div>}
            </div>)}
            {lastPage > 1 && <div className="flex gap-2"><Button type="button" disabled={busy || page === 1} onClick={() => setPage(page - 1)}>Previous</Button><span>Page {page} of {lastPage}</span><Button type="button" disabled={busy || page === lastPage} onClick={() => setPage(page + 1)}>Next</Button></div>}
        </section>
    </details>;
}
