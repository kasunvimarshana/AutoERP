import { useEffect, useRef, useState, type FormEvent } from 'react';
import { toApiError, ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatBusinessDate } from '@/shared/utils/businessDate';
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
        if (until && from && until < from) {
            setError(new ApiError(
                'Estimate through date must follow estimate from date.',
                422,
                'VALIDATION_FAILED',
                'validation',
                { until: ['Estimate through date must follow estimate from date.'] },
            ));
            return;
        }
        const controller = new AbortController(); request.current = controller;
        setBusy(true); setError(null); setResult(null);
        try {
            const value = await previewBaseRent(kind, agreement, from, until, controller.signal);
            if (!controller.signal.aborted) setResult(value);
        } catch (failure) { if (!controller.signal.aborted) setError(toApiError(failure)); }
        finally { if (!controller.signal.aborted) setBusy(false); }
    }
    return <details className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><summary className="cursor-pointer font-medium text-slate-900">Estimate base rent</summary>
        <p className="my-3 text-sm">Calendar-day estimate using the recorded base rate. Both selected dates are included.
            {agreement.basis === RentalBasis.Monthly && ' Monthly cycles start on the agreement anniversary, adjusted to the last day in shorter months; partial cycles use their actual number of days.'}
            {' '}This estimates base rent only. It does not include mileage, extras, deductions or tax, change the agreement, or create an invoice.</p>
        <form onSubmit={calculate} className="space-y-3">
            <Input label="Estimate from" type="date" required min={agreement.starts_on} max={agreement.ends_on ?? undefined} value={from} disabled={busy} onChange={event => { setFrom(event.target.value); setResult(null); }} error={error?.fields.from?.[0]} />
            <Input label="Estimate through" type="date" required max={agreement.ends_on ?? undefined} value={until} disabled={busy} onChange={event => { setUntil(event.target.value); setResult(null); }} error={error?.fields.until?.[0]} />
            <ErrorAlert error={error} inline />
            <Button type="submit" loading={busy} disabled={!from || !until || agreement.terms.base_rate === null}>Calculate base rent</Button>
            {agreement.terms.base_rate === null && <p className="text-sm text-amber-700">Record a base rental rate to calculate an estimate.</p>}
        </form>
        {result && <section aria-label="Base rent estimate" className="mt-4 space-y-3">
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p className="text-base font-semibold text-slate-900">Base rent: <MoneyDisplay value={result.base_rent} currency={result.currency} /></p>
                <p className="mt-1 text-xs text-slate-500">{result.agreement.reference} · revision {result.agreement.version} · rate <MoneyDisplay value={result.rate} currency={result.currency} /></p>
            </div>
            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table className="w-full text-left text-sm">
                    <caption className="sr-only">Calculation by period</caption>
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>{['Period', 'Days', 'Cycle days', 'Amount'].map(label => <th key={label} className="px-4 py-3">{label}</th>)}</tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {result.segments.map(segment => (
                            <tr key={segment.from} className="transition-colors hover:bg-blue-50/40">
                                <td className="px-4 py-3 text-slate-700">{formatBusinessDate(segment.from)} – {formatBusinessDate(segment.until)}</td>
                                <td className="px-4 py-3 text-slate-600">{segment.days}</td>
                                <td className="px-4 py-3 text-slate-600">{segment.cycle_from ? <>{segment.denominator_days} ({formatBusinessDate(segment.cycle_from)} – {formatBusinessDate(segment.cycle_until)})</> : 'Daily rate'}</td>
                                <td className="px-4 py-3 font-medium text-slate-900"><MoneyDisplay value={segment.amount} currency={result.currency} /></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <p className="text-xs text-slate-500">Calculations retain full internal precision so adjacent partial periods reconcile to the complete cycle; displayed amounts use the standard money format.</p>
        </section>}
    </details>;
}
