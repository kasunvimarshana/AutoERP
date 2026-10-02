import { useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { InvoiceStatus } from '@/modules/invoice/invoiceTypes';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { MileageAssessmentPanel } from './MileageAssessmentPanel';
import { AgreementKind } from './agreements';
import { BILLING_PERMISSION, type CreatedRentalInvoice } from './baseRentBillingApi';
import { RunningChartStatus, type RunningChart } from './runningCharts';
import { billUsage, loadUsageCharges, reissueUsage, voidUsage, USAGE_COMPONENT_LABELS, UsageChargeComponent, type UsageCharge } from './usageChargeApi';

enum ChargeAction { Create = 'create', Reissue = 'reissue', Void = 'void' }
const RELEASED = new Set<string>([InvoiceStatus.Cancelled, InvoiceStatus.Void, InvoiceStatus.Reversed]);
export function UsageChargePanel({ chart, hasOwner }: { chart: RunningChart; hasOwner: boolean }) {
    const auth = useAuth();
    const [side, setSide] = useState<AgreementKind | null>(null);
    const kinds = Object.values(AgreementKind).filter(kind => (kind === AgreementKind.Customer || hasOwner) && hasPermission(auth, BILLING_PERMISSION[kind]));
    if (!kinds.length || chart.status === RunningChartStatus.Draft) return null;
    return <section aria-label="Chart charges" className="space-y-3 border-t pt-3">
        <div className="flex flex-wrap gap-2">{kinds.map(kind => <Button key={kind} variant={side === kind ? 'primary' : 'secondary'} aria-expanded={side === kind} onClick={() => setSide(side === kind ? null : kind)}>{kind === AgreementKind.Customer ? 'Customer charges' : 'Owner payables'}</Button>)}</div>
        {side && kinds.includes(side) && <UsageChargeForm key={`${side}-${chart.id}-${chart.row_version}`} kind={side} chart={chart} />}
    </section>;
}
export function UsageChargeForm({ kind, chart }: { kind: AgreementKind; chart: RunningChart }) {
    const [mileage, setMileage] = useState(false);
    const [component, setComponent] = useState<UsageChargeComponent | ''>('');
    const [selected, setSelected] = useState<UsageCharge | null>(null);
    const [action, setAction] = useState(ChargeAction.Create);
    const [invoiceDate, setInvoiceDate] = useState(''); const [dueDate, setDueDate] = useState(''); const [exchange, setExchange] = useState('');
    const [reason, setReason] = useState(''); const [accepted, setAccepted] = useState(false);
    const [created, setCreated] = useState<CreatedRentalInvoice | null>(null); const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false); const [page, setPage] = useState(1); const [revision, setRevision] = useState(0); const inFlight = useRef(false);
    const charges = useApi(signal => loadUsageCharges(kind, chart, page, signal), [kind, chart.id, chart.row_version, page, revision]);
    const result = charges.data;
    function choose(charge: UsageCharge, next: ChargeAction) { setMileage(false); setSelected(charge); setAction(next); setAccepted(false); setReason(''); setCreated(null); setError(null); }
    async function submit(event: FormEvent) {
        event.preventDefault(); if (inFlight.current || !result || !accepted) return;
        inFlight.current = true; setBusy(true); setError(null); setCreated(null);
        const document = { invoice_date: invoiceDate, due_date: dueDate || null, exchange_rate: exchange };
        try {
            if (selected && action === ChargeAction.Void) await voidUsage(kind, chart, result.agreement.version, selected, reason);
            else if (selected) setCreated(await reissueUsage(kind, chart, result.agreement.version, selected, document));
            else if (component) setCreated(await billUsage(kind, chart, result.agreement.version, component, document));
            setSelected(null); setAction(ChargeAction.Create); setAccepted(false); setRevision(value => value + 1);
        } catch (failure) { setError(toApiError(failure)); }
        finally { inFlight.current = false; setBusy(false); }
    }
    const quote = result?.components.find(value => value.component === component);
    return <div className="space-y-3">
        <p className="font-medium">{kind === AgreementKind.Customer ? 'Customer charges' : 'Owner payables'} · {chart.reference}</p>
        <p className="text-sm">Bill a recorded OT or night-out component using this side’s assigned agreement. OT uses minutes × the category’s hourly rate ÷ 60, without another multiplier. Night-outs use count × agreed rate. Blank values cannot be billed. Review and post the draft in Invoice.</p>
        <ErrorAlert error={error ?? charges.error} inline />
        {charges.loading ? <LoadingState label={kind === AgreementKind.Customer ? 'Loading customer charges…' : 'Loading owner payables…'} /> : result && <><p className="text-sm text-slate-600">Agreement {result.agreement.reference}</p>
        {chart.status === RunningChartStatus.Finalized && <Button variant="secondary" disabled={busy} onClick={() => { setMileage(!mileage); setSelected(null); setAction(ChargeAction.Create); setAccepted(false); setError(null); }}>{mileage ? 'OT and night-outs' : 'Assess mileage'}</Button>}
        {mileage && <MileageAssessmentPanel kind={kind} chart={chart} onSaved={() => setRevision(value => value + 1)} />}
        {!mileage && chart.status === RunningChartStatus.Finalized && <form aria-label="Bill chart component" onSubmit={submit} className="space-y-3">
            {selected ? <p>{action === ChargeAction.Void ? 'Void' : 'Reissue'}: {selected.calculation.description} <Button type="button" variant="secondary" disabled={busy} onClick={() => { setSelected(null); setAction(ChargeAction.Create); setAccepted(false); setError(null); }}>Cancel selection</Button></p> : <Select label="Component" required value={component} options={Object.entries(USAGE_COMPONENT_LABELS).map(([value, label]) => ({ value, label }))} onChange={e => { setComponent(e.target.value as UsageChargeComponent | ''); setAccepted(false); }} error={error?.fields.component?.[0]} />}
            {!selected && quote && <p role="status">{quote.quantity ?? 'Not recorded'} {component === UsageChargeComponent.NightOut ? 'nights' : 'minutes'} · rate {quote.rate === null ? 'Not recorded' : <MoneyDisplay value={quote.rate} currency={result.currency} />} {component === UsageChargeComponent.NightOut ? 'per night' : 'per hour'} · {quote.amount === null ? 'Cannot calculate' : <MoneyDisplay value={quote.amount} currency={result.currency} />}{quote.error && ` · ${quote.error}`}</p>}
            {action === ChargeAction.Void ? <Textarea label="Void reason" required value={reason} disabled={busy} onChange={e => setReason(e.target.value)} error={error?.fields.reason?.[0]} /> : <>
                <Input label="Invoice date" type="date" required value={invoiceDate} disabled={busy} onChange={e => setInvoiceDate(e.target.value)} error={error?.fields.invoice_date?.[0]} />
                <Input label="Due date (optional)" type="date" min={invoiceDate} value={dueDate} disabled={busy} onChange={e => setDueDate(e.target.value)} error={error?.fields.due_date?.[0]} />
                <Input label="Exchange rate to base currency" required inputMode="decimal" value={exchange} disabled={busy} onChange={e => setExchange(e.target.value)} error={error?.fields.exchange_rate?.[0]} />
            </>}
            <label className="flex gap-2"><input type="checkbox" checked={accepted} disabled={busy} onChange={e => setAccepted(e.target.checked)} />{action === ChargeAction.Void ? 'Void this released charge and retain its calculation history.' : selected ? 'Reissue the unchanged charge with these document details.' : 'Apply the recorded-minutes and nights policy to this component.'}</label>
            <Button type="submit" loading={busy} disabled={!accepted || (action === ChargeAction.Void ? !reason.trim() : !invoiceDate || !exchange || (!selected && (!component || !quote || !!quote.error)))}>{action === ChargeAction.Void ? 'Void charge' : 'Create invoice draft'}</Button>
        </form>}
        {created && <p role="status">Created <Link to={`/invoices/${created.id}`} className="underline">{created.invoice_number}</Link> · <MoneyDisplay value={created.grand_total} currency={result.currency} /></p>}
        {result.charges.data.length === 0 && !mileage && <p className="text-sm text-slate-500">No usage charges have been recorded on this side.</p>}
        {result.charges.data.map(charge => <article key={charge.id} className="space-y-2 border-t pt-2"><p>{charge.calculation.description} · <MoneyDisplay value={charge.amount} currency={charge.calculation.currency} /></p>
            {charge.invoices.map(invoice => <p key={invoice.id} className="flex flex-wrap items-center gap-2"><Link to={`/invoices/${invoice.id}`} className="underline">{invoice.number}</Link><StatusBadge status={invoice.status} /></p>)}
            {charge.voided_at ? <p>Voided: {charge.void_reason}</p> : chart.status === RunningChartStatus.Finalized && charge.invoices.every(invoice => RELEASED.has(invoice.status)) && <div className="flex gap-2">{charge.invoices.length > 0 && <Button variant="secondary" disabled={busy} onClick={() => choose(charge, ChargeAction.Reissue)}>Reissue charge</Button>}<Button variant="secondary" disabled={busy} onClick={() => choose(charge, ChargeAction.Void)}>Void charge</Button></div>}
        </article>)}
        {result.charges.last_page > 1 && <div className="flex flex-wrap items-center gap-2"><Button disabled={busy || page === 1} onClick={() => setPage(page - 1)}>Previous</Button><span className="text-sm text-slate-600">Page {page} of {result.charges.last_page}</span><Button disabled={busy || page === result.charges.last_page} onClick={() => setPage(page + 1)}>Next</Button></div>}
        <Button variant="secondary" disabled={busy} onClick={() => { setSelected(null); setAction(ChargeAction.Create); setAccepted(false); setError(null); setRevision(value => value + 1); }}>Reload charges</Button>
        </>}
    </div>;
}
