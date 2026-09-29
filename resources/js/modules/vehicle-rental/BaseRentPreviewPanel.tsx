import { useEffect, useRef, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { RentalBasis, type Agreement, type AgreementKind } from './agreements';
import { previewBaseRent, type BaseRentPreview } from './baseRentPreviewApi';

export function BaseRentPreviewPanel({ kind, agreement }: { kind: AgreementKind; agreement: Agreement }) {
    const [from, setFrom] = useState(agreement.starts_on);
    const [until, setUntil] = useState('');
    const [result, setResult] = useState<BaseRentPreview | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const request = useRef<AbortController | null>(null);
    useEffect(() => () => request.current?.abort(), []);
    async function calculate(event: FormEvent) {
        event.preventDefault();
        const controller = new AbortController(); request.current = controller;
        setBusy(true); setError(null); setResult(null);
        try {
            const value = await previewBaseRent(kind, agreement, from, until, controller.signal);
            if (!controller.signal.aborted) setResult(value);
        } catch (failure) { if (!controller.signal.aborted) setError(toApiError(failure)); }
        finally { if (!controller.signal.aborted) setBusy(false); }
    }
    return <details className="rounded border p-3"><summary className="cursor-pointer font-medium">Estimate base rent</summary>
        <p className="my-3 text-sm">Calendar-day estimate using the recorded base rate. Both selected dates are included.
            {agreement.basis === RentalBasis.Monthly && ' Monthly cycles start on the agreement anniversary, adjusted to the last day in shorter months; partial cycles use their actual number of days.'}
            {' '}This estimates base rent only. It does not include mileage, extras, deductions or tax, change the agreement, or create an invoice.</p>
        <form onSubmit={calculate} className="space-y-3">
            <Input label="Estimate from" type="date" required min={agreement.starts_on} max={agreement.ends_on ?? undefined} value={from} disabled={busy} onChange={event => { setFrom(event.target.value); setResult(null); }} error={error?.fields.from?.[0]} />
            <Input label="Estimate through" type="date" required min={from} max={agreement.ends_on ?? undefined} value={until} disabled={busy} onChange={event => { setUntil(event.target.value); setResult(null); }} error={error?.fields.until?.[0]} />
            <ErrorAlert error={error} inline />
            <Button type="submit" loading={busy} disabled={!from || !until || agreement.terms.base_rate === null}>Calculate base rent</Button>
            {agreement.terms.base_rate === null && <p>Record a base rental rate to calculate an estimate.</p>}
        </form>
        {result && <section aria-label="Base rent estimate" className="mt-4 overflow-x-auto">
            <p className="font-semibold">Base rent: {result.currency} {result.base_rent}</p>
            <p className="text-sm">{result.agreement.reference} · revision {result.agreement.version} · rate {result.rate}</p>
            <table className="w-full text-left text-sm"><caption>Calculation by period</caption><thead><tr><th>Period</th><th>Days</th><th>Cycle days</th><th>Amount</th></tr></thead>
                <tbody>{result.segments.map(segment => <tr key={segment.from}><td>{segment.from} – {segment.until}</td><td>{segment.days}</td><td>{segment.cycle_from ? `${segment.denominator_days} (${segment.cycle_from} – ${segment.cycle_until})` : 'Daily rate'}</td><td>{segment.amount}</td></tr>)}</tbody>
            </table>
            <p className="text-xs text-slate-500">Six-decimal cumulative allocation keeps adjacent partial periods equal to their complete cycle.</p>
        </section>}
    </details>;
}
