import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Panel } from '@/shared/components/Panel';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { AgreementKind } from './agreements';
import { getAgreement, searchCustomerAgreements } from './agreementApi';
import { fetchRunningChartReport, getRunningChartReportExportUrl, type RunningChartReportData } from './runningChartApi';

export default function RentalRunningChartReportPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const today = businessDateInputValue();
    const firstDayOfMonth = today.slice(0, 8) + '01';

    const [agreement, setAgreement] = useState<NamedResource | null>(null);
    const [startDate, setStartDate] = useState<string>(searchParams.get('start_date') || firstDayOfMonth);
    const [endDate, setEndDate] = useState<string>(searchParams.get('end_date') || today);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [report, setReport] = useState<RunningChartReportData | null>(null);

    useEffect(() => {
        const agId = searchParams.get('agreement_id');
        if (agId) {
            const parsed = parseInt(agId, 10);
            if (!isNaN(parsed) && parsed > 0) {
                void getAgreement(AgreementKind.Customer, parsed)
                    .then(ag => {
                        setAgreement({
                            id: ag.id,
                            name: `${ag.reference} — ${ag.party.name}`,
                        });
                    })
                    .catch(() => {
                        // ignore prefetch error
                    });
                void loadReport(parsed, startDate, endDate);
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function loadReport(id: number, start: string, end: string) {
        if (!id || !start || !end) return;
        setLoading(true);
        setError(null);
        try {
            const data = await fetchRunningChartReport({
                agreement_id: id,
                start_date: start,
                end_date: end,
            });
            setReport(data);
            setSearchParams({ agreement_id: String(id), start_date: start, end_date: end });
        } catch (err) {
            setError(toApiError(err));
            setReport(null);
        } finally {
            setLoading(false);
        }
    }

    function handleGenerate(e: FormEvent) {
        e.preventDefault();
        if (!agreement?.id) {
            return;
        }
        void loadReport(agreement.id, startDate, endDate);
    }

    const exportPdfUrl = report
        ? getRunningChartReportExportUrl({ agreement_id: report.header.agreement_id, start_date: report.header.start_date, end_date: report.header.end_date }, 'pdf')
        : '#';

    const exportPrintUrl = report
        ? getRunningChartReportExportUrl({ agreement_id: report.header.agreement_id, start_date: report.header.start_date, end_date: report.header.end_date }, 'print')
        : '#';

    return (
        <div className="space-y-5">
            <ContentHeader
                title="Running Chart report (31-day matrix sheet)"
                description="Generate the official running chart matrix sheet matching monthly basis vehicle operations with daily mileage, OT, night outs, and excess kilometer analysis."
            />

            <Panel title="Report Parameters">
                <form aria-label="Generate running chart report" onSubmit={handleGenerate} className="grid gap-3 md:grid-cols-4 items-end">
                    <LookupSelect
                        label="Customer Agreement"
                        placeholder="Search agreement or customer..."
                        value={agreement}
                        onChange={setAgreement}
                        search={searchCustomerAgreements}
                        required
                        error={error?.fields.agreement_id?.[0]}
                    />
                    <Input
                        label="Period start date"
                        type="date"
                        required
                        value={startDate}
                        onChange={e => setStartDate(e.target.value)}
                        error={error?.fields.start_date?.[0]}
                    />
                    <Input
                        label="Period end date"
                        type="date"
                        required
                        min={startDate}
                        value={endDate}
                        onChange={e => setEndDate(e.target.value)}
                        error={error?.fields.end_date?.[0]}
                    />
                    <div>
                        <Button type="submit" loading={loading} disabled={!agreement} className="w-full">
                            Generate Sheet
                        </Button>
                    </div>
                </form>
            </Panel>

            <ErrorAlert error={error} inline />

            {loading ? (
                <LoadingState label="Compiling running chart matrix and calculations…" />
            ) : report ? (
                <div className="space-y-4">
                    {/* Toolbar */}
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Official Document</span>
                            <h2 className="text-lg font-bold text-slate-900">
                                {report.header.report_title}
                            </h2>
                            <p className="text-sm text-slate-600">
                                Agreement: <strong className="text-slate-900">{report.header.agreement_number}</strong> • Client: <strong className="text-slate-900">{report.header.client_name}</strong> • Vehicle: <strong className="text-slate-900">{report.header.agreement_vehicle_number}</strong>
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <a
                                href={exportPrintUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Print Sheet
                            </a>
                            <a
                                href={exportPdfUrl}
                                download
                                className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                Download PDF
                            </a>
                        </div>
                    </div>

                    {/* Report Matrix Table */}
                    <div className="overflow-x-auto rounded-xl border border-slate-300 bg-white shadow-sm">
                        <table className="w-full border-collapse text-left text-xs text-slate-700">
                            <thead>
                                <tr className="border-b border-slate-300 bg-slate-100 text-slate-800 font-bold uppercase tracking-wider">
                                    <th className="p-2 border-r border-slate-300">Date</th>
                                    <th className="p-2 border-r border-slate-300 text-right">Start KM</th>
                                    <th className="p-2 border-r border-slate-300 text-right">Finish KM</th>
                                    <th className="p-2 border-r border-slate-300 text-right">Day KM</th>
                                    <th className="p-2 border-r border-slate-300 text-right font-black">Cumul. KM</th>
                                    <th className="p-2 border-r border-slate-300">On Time</th>
                                    <th className="p-2 border-r border-slate-300">Off Time</th>
                                    <th className="p-2 border-r border-slate-300 text-right">OT Hours</th>
                                    <th className="p-2 border-r border-slate-300 text-center">OT Type</th>
                                    <th className="p-2 border-r border-slate-300 text-center">Night Out</th>
                                    <th className="p-2 border-r border-slate-300 text-right">Garage KM</th>
                                    <th className="p-2 border-r border-slate-300">Driver</th>
                                    <th className="p-2">Remarks</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {report.rows.map(row => (
                                    <tr
                                        key={row.date}
                                        className={row.is_active_day ? 'hover:bg-slate-50' : 'bg-slate-50/50 text-slate-400'}
                                    >
                                        <td className="p-2 border-r border-slate-200 font-semibold">{row.date}</td>
                                        <td className="p-2 border-r border-slate-200 text-right font-mono">{row.start_odometer || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-right font-mono">{row.finish_odometer || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-right font-mono font-bold text-slate-900">{row.comparative_mileage || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-right font-mono font-black text-blue-900">{row.cumulative_mileage || '-'}</td>
                                        <td className="p-2 border-r border-slate-200">{row.on_time || '-'}</td>
                                        <td className="p-2 border-r border-slate-200">{row.off_time || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-right font-semibold text-amber-900">{row.ot_hours || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-center">{row.ot_type || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-center font-bold">{row.night_out || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 text-right">{row.garage_km || '-'}</td>
                                        <td className="p-2 border-r border-slate-200 font-medium">{row.driver_name || '-'}</td>
                                        <td className="p-2 text-slate-500 truncate max-w-xs">{row.remarks || '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase text-slate-500">Comparative Distance</span>
                            <p className="mt-1 text-2xl font-black text-slate-900">
                                {report.summary.total_comparative_mileage.toLocaleString()} <span className="text-sm font-normal text-slate-500">km</span>
                            </p>
                            <span className="text-xs text-slate-500">Final Cumulative: {report.summary.final_cumulative_mileage.toLocaleString()} km</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase text-slate-500">Contract Allowance</span>
                            <p className="mt-1 text-2xl font-black text-blue-900">
                                {report.summary.included_km.toLocaleString()} <span className="text-sm font-normal text-slate-500">km</span>
                            </p>
                            <span className="text-xs text-slate-500">Terms Included Distance</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase text-slate-500">Excess Distance</span>
                            <p className={`mt-1 text-2xl font-black ${report.summary.excess_km > 0 ? 'text-rose-600' : 'text-emerald-600'}`}>
                                {report.summary.excess_km.toLocaleString()} <span className="text-sm font-normal text-slate-500">km</span>
                            </p>
                            <span className="text-xs text-slate-500">Billable Excess Overage</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase text-slate-500">Overtime & Night Outs</span>
                            <p className="mt-1 text-2xl font-black text-amber-700">
                                {report.summary.total_ot_hours} <span className="text-sm font-normal text-slate-500">hrs</span>
                            </p>
                            <span className="text-xs text-slate-500">Night Out Halts: {report.summary.total_night_out}</span>
                        </div>
                    </div>

                    {/* Signatures */}
                    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="grid grid-cols-3 gap-8 text-center text-xs text-slate-600">
                            <div>
                                <div className="border-t border-dashed border-slate-400 pt-2 font-bold uppercase text-slate-800">
                                    Driver's Signature
                                </div>
                                <span className="text-slate-400">Assigned Chauffeur</span>
                            </div>
                            <div>
                                <div className="border-t border-dashed border-slate-400 pt-2 font-bold uppercase text-slate-800">
                                    Lessee / Client Signature
                                </div>
                                <span className="text-slate-400">Authorized Representative</span>
                            </div>
                            <div>
                                <div className="border-t border-dashed border-slate-400 pt-2 font-bold uppercase text-slate-800">
                                    Management Verification
                                </div>
                                <span className="text-slate-400">AutoERP Fleet Control</span>
                            </div>
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
