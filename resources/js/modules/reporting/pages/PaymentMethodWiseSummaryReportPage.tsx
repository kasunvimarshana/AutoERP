import { useEffect, useState, type FormEvent } from 'react';
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
import { runPaymentMethodWiseSummaryReport } from '../reportingApi';
import type { PaymentMethodWiseParams, PaymentMethodWiseResult } from '../reportingTypes';

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

    const apply = (event: FormEvent) => {
        event.preventDefault();
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
                    <div className="overflow-x-auto">
                        <table className="min-w-full border-collapse text-sm">
                            <thead className="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th className="px-3 py-3">Date</th>
                                    <th className="px-3 py-3">Currency</th>
                                    {result.methods.map((method) => <th key={method.key} className="px-3 py-3 text-right">{method.name}</th>)}
                                    <th className="px-3 py-3 text-right">Receipts total</th>
                                    <th className="px-3 py-3 text-right">New credit sales</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {result.days.map((day) => <tr key={`${day.date}-${day.currency}`}>
                                    <td className="px-3 py-3 font-medium">{day.date}</td>
                                    <td className="px-3 py-3">{day.currency}</td>
                                    {result.methods.map((method) => <td key={method.key} className="px-3 py-3 text-right tabular-nums">{formatMoney(day.methods[method.key], day.currency)}</td>)}
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{formatMoney(day.receipts, day.currency)}</td>
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{formatMoney(day.credit_sales, day.currency)}</td>
                                </tr>)}
                                {result.days.length === 0 ? <tr><td colSpan={result.methods.length + 4} className="px-3 py-10 text-center text-slate-500">No posted invoice receipts or new credit sales in this period.</td></tr> : null}
                            </tbody>
                            <tfoot className="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                                {result.totals.map((total) => <tr key={total.currency}>
                                    <td className="px-3 py-3">Total</td>
                                    <td className="px-3 py-3">{total.currency}</td>
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
