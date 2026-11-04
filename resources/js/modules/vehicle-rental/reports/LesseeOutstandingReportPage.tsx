import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    fetchLesseeOutstandingReport,
    getLesseeOutstandingReportExportUrl,
    LesseeOutstandingReportData,
    LesseeOutstandingReportParams,
} from '../rentalReportsApi';

export default function LesseeOutstandingReportPage() {
    const today = businessDateInputValue();

    const [params, setParams] = useState<LesseeOutstandingReportParams>({
        as_of_date: today,
        aging_bucket: 'all',
    });

    const [data, setData] = useState<LesseeOutstandingReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: LesseeOutstandingReportParams) => {
        setLoading(true);
        setError(null);
        fetchLesseeOutstandingReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load lessee outstanding report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchLesseeOutstandingReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load lessee outstanding report.';
                    setError(msg);
                }
            })
            .finally(() => {
                if (active) setLoading(false);
            });

        return () => {
            active = false;
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeFetch(params);
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Lessee's Outstanding Age Analysis"
                description="Receivables aging analysis for commercial vehicle rentals grouped by customer, agreement, and individual vehicle."
                actions={
                    <div className="flex items-center gap-2">
                        <Link
                            to="/vehicle-rental/reports"
                            className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                        >
                            &larr; Reports Hub
                        </Link>
                        {data && (
                            <>
                                <a
                                    href={getLesseeOutstandingReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getLesseeOutstandingReportExportUrl(params, 'pdf')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 shadow-sm"
                                >
                                    Download PDF
                                </a>
                            </>
                        )}
                    </div>
                }
            />

            {/* Filter Parameter Form */}
            <Panel title="Report As-Of Date & Filter">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">As-Of Date</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.as_of_date || ''}
                            onChange={e => setParams(p => ({ ...p, as_of_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Aging Category</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.aging_bucket}
                            onChange={e => setParams(p => ({ ...p, aging_bucket: e.target.value as LesseeOutstandingReportParams['aging_bucket'] }))}
                        >
                            <option value="all">All Outstanding Invoices</option>
                            <option value="0_30">Current (0 - 30 Days)</option>
                            <option value="31_60">Overdue (31 - 60 Days)</option>
                            <option value="61_90">Overdue (61 - 90 Days)</option>
                            <option value="90_plus">Severely Overdue (&gt; 90 Days)</option>
                        </select>
                    </div>
                    <div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Calculating...' : 'Recalculate Aging'}
                        </button>
                    </div>
                </form>
            </Panel>

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* KPI Metrics */}
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Active Lessees</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_lessees_count}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Open Invoices</div>
                            <div className="mt-1 text-2xl font-bold text-blue-900">{data.summary.total_invoices_count}</div>
                        </div>
                        <div className="rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Total Invoiced</div>
                            <div className="mt-1 text-2xl font-bold text-purple-900">
                                {Number(data.summary.grand_total_original_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-red-700">Total Outstanding</div>
                            <div className="mt-1 text-2xl font-bold text-red-900">
                                {Number(data.summary.grand_total_balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {/* Grouped Tables per Lessee */}
                    <div className="space-y-4">
                        {data.groups.map(grp => (
                            <div key={grp.customer_id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono text-xs font-bold text-slate-500">{grp.lessee_code}</span>
                                        <span className="text-sm font-bold text-slate-900">{grp.lessee_name}</span>
                                    </div>
                                    <div className="text-xs text-slate-500 flex items-center gap-3 mt-1 sm:mt-0">
                                        {grp.phone && <span>Tel: <strong className="text-slate-700">{grp.phone}</strong></span>}
                                        {grp.contact_person && <span>Contact: <strong className="text-slate-700">{grp.contact_person}</strong></span>}
                                    </div>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-100/50 text-slate-600">
                                                <th className="py-2 px-3 font-semibold">Txn. Type</th>
                                                <th className="py-2 px-3 font-semibold text-center">Date</th>
                                                <th className="py-2 px-3 font-semibold">Invoice No</th>
                                                <th className="py-2 px-3 font-semibold">Agreement / Duration</th>
                                                <th className="py-2 px-3 font-semibold">Vehicle No</th>
                                                <th className="py-2 px-3 font-semibold text-right">Original Amount</th>
                                                <th className="py-2 px-3 font-semibold text-right">Balance Amount</th>
                                                <th className="py-2 px-3 font-semibold text-center">No of Days</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {grp.invoices.map(inv => (
                                                <tr key={inv.invoice_id} className="hover:bg-slate-50/60">
                                                    <td className="py-2 px-3 text-slate-600">{inv.txn_type}</td>
                                                    <td className="py-2 px-3 text-center text-slate-700">{inv.invoice_date}</td>
                                                    <td className="py-2 px-3 font-bold text-blue-600">{inv.invoice_number}</td>
                                                    <td className="py-2 px-3 text-slate-700">{inv.agreement_duration}</td>
                                                    <td className="py-2 px-3 font-semibold text-slate-800">{inv.vehicle_no}</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-700">
                                                        {Number(inv.original_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                                        {Number(inv.balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2 px-3 text-center font-semibold">
                                                        <span className={inv.age_days > 90 ? 'text-red-600 font-bold' : inv.age_days > 30 ? 'text-amber-600' : 'text-slate-700'}>
                                                            {inv.age_days}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                            <tr className="bg-slate-50 font-bold border-t border-slate-300">
                                                <td colSpan={5} className="py-2.5 px-3 text-right uppercase text-slate-600 text-[11px]">
                                                    Subtotal for {grp.lessee_name}:
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                    {Number(grp.subtotal_original_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-red-700">
                                                    {Number(grp.subtotal_balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ))}

                        {data.groups.length === 0 && (
                            <Panel>
                                <div className="py-12 text-center text-slate-400 text-xs">
                                    No outstanding invoices found for the specified criteria.
                                </div>
                            </Panel>
                        )}
                    </div>
                </>
            )}
        </div>
    );
}
