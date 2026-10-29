import { Fragment, useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { formatMoney } from '@/shared/utils/formatMoney';
import { ExportActions } from '../components/ExportActions';
import { loadPaymentMethodWiseDocuments, runPaymentMethodWiseSummaryReport } from '../reportingApi';
import type { PaymentMethodWiseDay, PaymentMethodWiseParams, PaymentMethodWiseResult } from '../paymentMethodWiseSummaryTypes';

const SOURCES = [
    { value: 'all', label: 'Selling + Vehicle Service' },
    { value: 'selling', label: 'Selling only' },
    { value: 'vehicle_service', label: 'Vehicle Service only' },
] as const;

function currentMonth(): PaymentMethodWiseParams {
    const today = new Date();
    const dateTo = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
    return { date_from: `${dateTo.slice(0, 7)}-01`, date_to: dateTo, source: 'all' };
}

export default function PaymentMethodWiseSummaryReportPage() {
    const [draft, setDraft] = useState<PaymentMethodWiseParams>(currentMonth);
    const [filters, setFilters] = useState<PaymentMethodWiseParams>(currentMonth);
    const [loaded, setLoaded] = useState<{ filters: PaymentMethodWiseParams; result: PaymentMethodWiseResult | null; error: ApiError | null } | null>(null);
    const [openKey, setOpenKey] = useState<string | null>(null);
    const [details, setDetails] = useState<{ key: string; rows: PaymentMethodWiseDay[]; error: ApiError | null } | null>(null);
    const loading = loaded?.filters !== filters;
    const result = loaded?.filters === filters ? loaded.result : null;
    const error = loaded?.filters === filters ? loaded.error : null;

    useEffect(() => {
        const controller = new AbortController();
        void runPaymentMethodWiseSummaryReport(filters, controller.signal)
            .then((data) => { if (!controller.signal.aborted) setLoaded({ filters, result: data, error: null }); })
            .catch((reason) => { if (!controller.signal.aborted) setLoaded({ filters, result: null, error: toApiError(reason) }); });
        return () => controller.abort();
    }, [filters]);

    useEffect(() => {
        if (!openKey) return;
        const controller = new AbortController();
        const [date] = openKey.split('|');
        void loadPaymentMethodWiseDocuments(filters, date, controller.signal)
            .then((rows) => { if (!controller.signal.aborted) setDetails({ key: openKey, rows, error: null }); })
            .catch((reason) => { if (!controller.signal.aborted) setDetails({ key: openKey, rows: [], error: toApiError(reason) }); });
        return () => controller.abort();
    }, [filters, openKey]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setOpenKey(null);
        setDetails(null);
        setFilters({ ...draft });
    };

    return (
        <>
            <ContentHeader
                title="Payment Method Wise Summary"
                description="Daily invoice receipts by method, alongside new credit sales."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />
            <Panel title="Report filters">
                <form className="grid gap-4 md:grid-cols-[1fr_1fr_1.5fr_auto] md:items-end" onSubmit={apply}>
                    <Input label="From" type="date" required value={draft.date_from} max={draft.date_to} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))} />
                    <Input label="To" type="date" required value={draft.date_to} min={draft.date_from} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))} />
                    <Select label="Receipt source" value={draft.source} options={[...SOURCES]} onChange={(event) => setDraft((current) => ({ ...current, source: event.target.value as PaymentMethodWiseParams['source'] }))} />
                    <Button type="submit" loading={loading}>Apply</Button>
                </form>
            </Panel>

            {loading && !result ? <LoadingState label="Loading payment method summary..." /> : null}
            {result ? <div className="mt-5 space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="font-semibold text-slate-900">{result.organization_unit}</p>
                        <p className="text-sm text-slate-500">Receipts follow payment date. New credit sales follow invoice date.</p>
                    </div>
                    <ExportActions reportKey="payment-method-wise-summary" params={filters} />
                </div>
                <div className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-950">
                    New credit sales are original sales recorded on credit. Later collections appear on their payment date and reduce invoice outstanding; the two totals are shown separately to avoid double counting.
                </div>
                <Panel title="Daily summary">
                    <div className="max-h-[75vh] overflow-auto print:max-h-none print:overflow-visible">
                        <table className="min-w-full border-collapse text-sm">
                            <thead className="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th className="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 px-3 py-3">Date</th>
                                    {result.methods.map((method) => <th key={method.key} className="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right">{method.name}</th>)}
                                    <th className="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right">Receipts total</th>
                                    <th className="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right">New credit sales</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {result.days.map((day) => {
                                    const key = `${day.date}|${day.currency}`;
                                    const expanded = openKey === key;
                                    const documents = expanded && details?.key === key ? details.rows.find((row) => row.currency === day.currency)?.documents : undefined;
                                    return <Fragment key={key}>
                                    <tr>
                                    <td className="px-3 py-3 font-medium">
                                        <button type="button" aria-expanded={expanded} onClick={() => setOpenKey(expanded ? null : key)} className="text-left font-semibold text-blue-700 hover:underline">
                                            <span aria-hidden="true">{expanded ? '▾' : '▸'} </span>{day.date}
                                            <span className="block pl-4 text-xs font-normal text-slate-500">{day.currency}</span>
                                        </button>
                                    </td>
                                    {result.methods.map((method) => <td key={method.key} className="px-3 py-3 text-right tabular-nums">{formatMoney(day.methods[method.key], day.currency)}</td>)}
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{formatMoney(day.receipts, day.currency)}</td>
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{formatMoney(day.credit_sales, day.currency)}</td>
                                    </tr>
                                    {expanded ? <tr><td colSpan={result.methods.length + 3} className="bg-slate-50 px-4 py-4">
                                        {details?.key !== key ? <p className="text-sm text-slate-500">Loading documents...</p> : null}
                                        {details?.key === key && details.error ? <ErrorAlert error={details.error} /> : null}
                                        {documents ? <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                            <table className="min-w-full text-sm">
                                                <thead className="bg-slate-50 text-left text-slate-600"><tr><th className="px-3 py-2">Type</th><th className="px-3 py-2">Document</th><th className="px-3 py-2">Invoice</th><th className="px-3 py-2">Method</th><th className="px-3 py-2 text-right">Amount</th></tr></thead>
                                                <tbody className="divide-y divide-slate-100">{documents.map((document, index) => <tr key={`${document.type}-${document.payment?.id ?? 'credit'}-${document.invoice.id}-${index}`}>
                                                    <td className="px-3 py-2">{document.type === 'receipt' ? 'Receipt' : 'Credit sale'}</td>
                                                    <td className="px-3 py-2">{document.payment ? <Link to={`/payments/${document.payment.id}`} className="font-semibold text-blue-700 hover:underline">{document.payment.number}</Link> : <Link to={`/invoices/${document.invoice.id}`} className="font-semibold text-blue-700 hover:underline">{document.invoice.number}</Link>}</td>
                                                    <td className="px-3 py-2">{document.payment ? <Link to={`/invoices/${document.invoice.id}`} className="text-blue-700 hover:underline">{document.invoice.number}</Link> : '—'}</td>
                                                    <td className="px-3 py-2">{Object.entries(document.methods).map(([method, amount]) => `${result.methods.find((entry) => entry.key === method)?.name ?? method}: ${formatMoney(amount, day.currency)}`).join(', ') || '—'}</td>
                                                    <td className="px-3 py-2 text-right font-semibold tabular-nums">{formatMoney(document.amount, day.currency)}</td>
                                                </tr>)}</tbody>
                                            </table>
                                        </div> : null}
                                    </td></tr> : null}
                                    </Fragment>;
                                })}
                                {result.days.length === 0 ? <tr><td colSpan={result.methods.length + 3} className="px-3 py-10 text-center text-slate-500">No posted invoice receipts or new credit sales in this period.</td></tr> : null}
                            </tbody>
                            <tfoot className="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                                {result.totals.map((total) => <tr key={total.currency}>
                                    <td className="px-3 py-3">Total ({total.currency})</td>
                                    {result.methods.map((method) => <td key={method.key} className="px-3 py-3 text-right tabular-nums">{formatMoney(total.methods[method.key], total.currency)}</td>)}
                                    <td className="px-3 py-3 text-right tabular-nums">{formatMoney(total.receipts, total.currency)}</td>
                                    <td className="px-3 py-3 text-right tabular-nums">{formatMoney(total.credit_sales, total.currency)}</td>
                                </tr>)}
                            </tfoot>
                        </table>
                    </div>
                </Panel>
            </div> : null}
        </>
    );
}
