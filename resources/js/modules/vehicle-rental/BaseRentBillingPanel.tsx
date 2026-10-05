import { useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { InvoiceStatus } from '@/modules/invoice/invoiceTypes';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { AgreementKind, type Agreement } from './agreements';
import { rentalBillingPresentation } from './billingPresentation';
import { billBaseRent, loadBaseCharges, reissueBaseRent, voidBaseCharge, type BaseCharge, type CreatedRentalInvoice } from './baseRentBillingApi';

enum ChargeAction { Reissue = 'reissue', Void = 'void' }
const RELEASED_INVOICE_STATES = new Set<string>([InvoiceStatus.Cancelled, InvoiceStatus.Void, InvoiceStatus.Reversed]);
export function BaseRentBillingPanel({ kind, agreement }: { kind: AgreementKind; agreement: Agreement }) {
    const billing = rentalBillingPresentation(kind);
    const [from, setFrom] = useState(agreement.starts_on);
    const [until, setUntil] = useState('');
    const [invoiceDate, setInvoiceDate] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [exchangeRate, setExchangeRate] = useState('');
    const [accepted, setAccepted] = useState(false);
    const [selected, setSelected] = useState<BaseCharge | null>(null);
    const [action, setAction] = useState(ChargeAction.Reissue);
    const [reason, setReason] = useState('');
    const [created, setCreated] = useState<CreatedRentalInvoice | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const inFlight = useRef(false);
    const chargeRequest = useApi(signal => loadBaseCharges(kind, agreement, page, signal), [kind, agreement.id, agreement.row_version, page, revision]);
    const charges = chargeRequest.data?.data ?? [];
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
        <p className="my-3 text-sm">{billing.createDraftDescription} from the recorded base rate and actual-calendar policy. Monthly partial periods use actual anniversary-cycle days. This bills base rent only; mileage, extras and deductions are separate. Tax uses the configured Tax rules. {billing.reviewDraftText}</p>
        <form onSubmit={submit} className="space-y-3">
            {selected ? <p>{action === ChargeAction.Void ? 'Void original charge:' : 'Reissue original charge:'} {formatBusinessDate(selected.from)} – {formatBusinessDate(selected.until)} · <MoneyDisplay value={selected.amount} currency={selected.currency} /> <Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(null); setAccepted(false); }}>Cancel selection</Button></p> : <>
                <Input label="Charge from" type="date" required min={agreement.starts_on} value={from} disabled={busy} onChange={e => { setFrom(e.target.value); setAccepted(false); }} error={error?.fields.from?.[0]} />
                <Input label="Charge through" type="date" required min={from} max={agreement.ends_on ?? undefined} value={until} disabled={busy} onChange={e => { setUntil(e.target.value); setAccepted(false); }} error={error?.fields.until?.[0]} />
            </>}
            {selected && action === ChargeAction.Void ? <Textarea label="Void reason" required value={reason} disabled={busy} onChange={e => setReason(e.target.value)} error={error?.fields.reason?.[0]} /> : <>
            <Input label={billing.documentDateLabel} type="date" required value={invoiceDate} disabled={busy} onChange={e => setInvoiceDate(e.target.value)} error={error?.fields.invoice_date?.[0]} />
            <Input label="Due date (optional)" type="date" min={invoiceDate} value={dueDate} disabled={busy} onChange={e => setDueDate(e.target.value)} error={error?.fields.due_date?.[0]} />
            <Input label="Exchange rate to base currency" required inputMode="decimal" value={exchangeRate} disabled={busy} onChange={e => setExchangeRate(e.target.value)} error={error?.fields.exchange_rate?.[0]} />
            <p className="text-sm">Enter the verified accounting exchange rate; use one when both currencies are the same.</p></>}
            <label className="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"><input className="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500" type="checkbox" checked={accepted} disabled={busy} onChange={e => setAccepted(e.target.checked)} /><span>{selected ? (action === ChargeAction.Void ? 'Void this charge after releasing its linked financial documents; retain its calculation history.' : 'Reissue this unchanged base charge using the document details above.') : 'Apply the stated actual-calendar policy to this base charge.'}</span></label>
            <ErrorAlert error={error ?? chargeRequest.error} inline />
            <Button type="submit" variant={selected && action === ChargeAction.Void ? 'danger' : 'primary'} loading={busy} disabled={chargeRequest.loading || !!chargeRequest.error || !accepted || (selected && action === ChargeAction.Void ? !reason.trim() : (!invoiceDate || !exchangeRate || (!selected && (!from || !until))))}>{selected && action === ChargeAction.Void ? 'Void charge' : billing.createDraftLabel}</Button>
        </form>
        {created && <p role="status" className="mt-3">Created {billing.documentName} <Link className="underline" to={`/invoices/${created.id}`}>{created.invoice_number}</Link> · total <MoneyDisplay value={created.grand_total} currency={agreement.currency.code} /></p>}
        <section aria-label="Recorded base charges" className="mt-4"><h3 className="font-medium">Recorded base charges</h3>
            {chargeRequest.loading ? <LoadingState label="Loading recorded base charges…" /> : chargeRequest.error ? null : charges.length === 0 ? <p className="mt-2 text-sm text-slate-500">No base-rent charges have been recorded.</p> : charges.map(charge => <div key={charge.id} className="my-2 border-t py-2"><p>{formatBusinessDate(charge.from)} – {formatBusinessDate(charge.until)} · <MoneyDisplay value={charge.amount} currency={charge.currency} /></p>
                {charge.invoices.map(invoice => <p key={invoice.id} className="flex flex-wrap items-center gap-2"><Link className="underline" to={`/invoices/${invoice.id}`}>{invoice.number}</Link><StatusBadge status={invoice.status} /></p>)}
                {charge.voided_at && <p>Voided: {charge.void_reason}</p>}
                {!charge.voided_at && charge.invoices.length > 0 && charge.invoices.every(invoice => RELEASED_INVOICE_STATES.has(invoice.status)) && <div className="flex flex-wrap gap-2"><Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(charge); setAction(ChargeAction.Reissue); setAccepted(false); setCreated(null); setError(null); }}>Reissue charge</Button><Button type="button" variant="danger" disabled={busy} onClick={() => { setSelected(charge); setAction(ChargeAction.Void); setReason(''); setAccepted(false); setCreated(null); setError(null); }}>Void charge</Button></div>}
            </div>)}
            <Pagination meta={chargeRequest.data} onPageChange={setPage} />
        </section>
    </details>;
}
