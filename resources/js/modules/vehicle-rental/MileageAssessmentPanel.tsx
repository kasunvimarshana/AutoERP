import { useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { useApi } from '@/shared/hooks/useApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { AgreementKind } from './agreements';
import type { RunningChart } from './runningCharts';
import { assessMileage, quoteMileage, type MileageAssessment } from './mileageApi';

export function MileageAssessmentPanel({ kind, chart, onSaved }: { kind: AgreementKind; chart: RunningChart; onSaved: () => void }) {
    const quoteRequest = useApi(signal => quoteMileage(kind, chart, signal), [kind, chart.id, chart.row_version]);
    const quote = quoteRequest.data;
    const [date, setDate] = useState(''); const [exchange, setExchange] = useState(''); const [due, setDue] = useState('');
    const [accepted, setAccepted] = useState(false); const [busy, setBusy] = useState(false); const [error, setError] = useState<ApiError | null>(null);
    const [recorded, setRecorded] = useState<MileageAssessment | null>(null); const inFlight = useRef(false);
    async function submit(event: FormEvent) {
        event.preventDefault(); if (!quote || !accepted || inFlight.current) return;
        inFlight.current = true; setBusy(true); setError(null);
        try { const result = await assessMileage(kind, chart, quote, { invoice_date: date, due_date: due || null, exchange_rate: exchange }); setRecorded(result); quoteRequest.setData(null); setAccepted(false); onSaved(); }
        catch (failure) { setError(toApiError(failure)); quoteRequest.setData(null); setAccepted(false); }
        finally { inFlight.current = false; setBusy(false); }
    }
    return <section aria-label="Mileage assessment" className="space-y-3">
        <p>Commercial KM uses one allowance per agreement calendar day or monthly anniversary cycle. Replacement vehicles share the same customer agreement allowance. Unused KM does not carry into another cycle. A partial final month receives an actual-days allowance. Zero-cost assessments still record used allowance.</p>
        <ErrorAlert error={error ?? quoteRequest.error} inline />
        {quoteRequest.loading ? <LoadingState label="Loading mileage quote…" /> : quote && <form aria-label="Assess commercial mileage" onSubmit={submit} className="space-y-3">
            <p>{quote.agreement.reference} · {quote.cycle_from} — {quote.cycle_until} · {quote.timezone}</p>
            <dl className="grid gap-3 sm:grid-cols-2">
                <div><dt className="text-sm text-slate-500">Cycle allowance</dt><dd><QuantityDisplay value={quote.allowance} /> km</dd></div>
                <div><dt className="text-sm text-slate-500">Recorded commercial distance</dt><dd><QuantityDisplay value={quote.distance} /> km</dd></div>
                <div><dt className="text-sm text-slate-500">Included distance applied</dt><dd><QuantityDisplay value={quote.included_applied} /> km</dd></div>
                <div><dt className="text-sm text-slate-500">Excess distance</dt><dd><QuantityDisplay value={quote.excess_km} /> km</dd></div>
                <div><dt className="text-sm text-slate-500">Agreed rate per KM</dt><dd><MoneyDisplay value={quote.rate} currency={quote.currency} /></dd></div>
                <div><dt className="text-sm text-slate-500">Amount before tax</dt><dd><MoneyDisplay value={quote.amount} currency={quote.currency} /></dd></div>
            </dl>
            <Input label="Invoice date" type="date" required value={date} disabled={busy} onChange={e => setDate(e.target.value)} />
            <Input label="Due date (optional)" type="date" min={date} value={due} disabled={busy} onChange={e => setDue(e.target.value)} />
            <Input label="Exchange rate to base currency" required inputMode="decimal" value={exchange} disabled={busy} onChange={e => setExchange(e.target.value)} />
            <p>Document inputs apply only when an amount is due. A zero assessment creates no invoice.</p>
            <label className="flex gap-2"><input type="checkbox" checked={accepted} disabled={busy} onChange={e => setAccepted(e.target.checked)} />Apply this calendar-cycle policy and the displayed allowance allocation.</label>
            <Button type="submit" loading={busy} disabled={!accepted || !date || !exchange}>Record mileage assessment</Button>
        </form>}
        {recorded && <p role="status">Mileage assessment recorded. {recorded.invoice ? <Link to={`/invoices/${recorded.invoice.id}`} className="underline">{recorded.invoice.invoice_number}</Link> : 'No invoice is due.'}</p>}
        {!recorded && <Button variant="secondary" disabled={busy || quoteRequest.loading} onClick={() => { setError(null); setAccepted(false); quoteRequest.reload(); }}>Reload mileage quote</Button>}
    </section>;
}
