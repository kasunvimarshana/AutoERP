import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    fetchOwnerDisbursementReport,
    getOwnerDisbursementReportExportUrl,
    OwnerDisbursementReportData,
    OwnerDisbursementReportParams,
} from '../rentalReportsApi';

export default function OwnerDisbursementReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<OwnerDisbursementReportParams>({
        start_date: startOfMonth,
        end_date: today,
        wht_rate: 10,
        report_type: 'summary',
    });

    const [activeTab, setActiveTab] = useState<'summary' | 'wht_ledger'>('summary');
    const [data, setData] = useState<OwnerDisbursementReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: OwnerDisbursementReportParams) => {
        setLoading(true);
        setError(null);
        fetchOwnerDisbursementReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load owner disbursements report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchOwnerDisbursementReport({ ...params, report_type: activeTab })
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load owner disbursements report.';
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
    }, [activeTab]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeFetch({ ...params, report_type: activeTab });
    };

    const currentExportParams: OwnerDisbursementReportParams = {
        ...params,
        report_type: activeTab,
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Owner Disbursement & Payables Ledger"
                description="Lessor settlements, monthly base lease entitlements, usage revenue sharing, toll/fuel deductions, and withholding tax (WHT) ledger statements."
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
                                    href={getOwnerDisbursementReportExportUrl(currentExportParams, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getOwnerDisbursementReportExportUrl(currentExportParams, 'pdf')}
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
            <Panel title="Settlement Period & Tax Policy">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Settlement Period From</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Settlement Period Until</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.end_date}
                            onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Withholding Tax (WHT %)</label>
                        <div className="relative">
                            <input
                                type="number"
                                step="0.5"
                                min="0"
                                max="100"
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none pr-7"
                                value={params.wht_rate ?? 10}
                                onChange={e => setParams(p => ({ ...p, wht_rate: Number(e.target.value) }))}
                            />
                            <span className="absolute right-2.5 top-2 text-xs font-semibold text-slate-400">%</span>
                        </div>
                    </div>
                    <div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Compiling Disbursements...' : 'Generate Payables Ledger'}
                        </button>
                    </div>
                </form>
            </Panel>

            {/* View Mode Tabs */}
            <div className="flex border-b border-slate-200">
                <button
                    type="button"
                    onClick={() => setActiveTab('summary')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 transition-colors ${
                        activeTab === 'summary'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Owner Settlements & Disbursement Summary
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('wht_ledger')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 transition-colors ${
                        activeTab === 'wht_ledger'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Withholding Tax (WHT) Ledger Account (:3305-000 WHT)
                </button>
            </div>

            {error && (
                <div className="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-2xs">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* KPI Metrics */}
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                        <div className="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Agreements</div>
                            <div className="mt-1 text-xl font-bold text-slate-900">{data.summary.total_owner_agreements}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Base Pay</div>
                            <div className="mt-1 text-lg font-bold text-blue-900">
                                {Number(data.summary.total_base_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-700">Usage Share</div>
                            <div className="mt-1 text-lg font-bold text-slate-900">
                                {(Number(data.summary.total_mileage_payable) + Number(data.summary.total_overtime_payable)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Gross Payable</div>
                            <div className="mt-1 text-lg font-bold text-blue-900">
                                {Number(data.summary.total_gross_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-amber-700">Deductions</div>
                            <div className="mt-1 text-lg font-bold text-amber-900">
                                {Number(data.summary.total_deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-rose-700">WHT ({params.wht_rate ?? 10}%)</div>
                            <div className="mt-1 text-lg font-bold text-rose-900">
                                {Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 shadow-sm">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Net Payable</div>
                            <div className="mt-1 text-lg font-bold text-emerald-900">
                                {Number(data.summary.total_net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {activeTab === 'summary' ? (
                        /* Standard Owner Settlements Table */
                        <Panel title={`Owner Settlements & Disbursements (${data.rows.length} Contracts)`}>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                            <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                            <th className="py-2.5 px-3 font-semibold">Vehicle Owner / Supplier</th>
                                            <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                            <th className="py-2.5 px-3 font-semibold">Basis</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Base Pay</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Usage Share</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Gross Pay</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Deductions</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">WHT ({params.wht_rate ?? 10}%)</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Net Payable</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {data.rows.map(row => (
                                            <tr key={row.owner_agreement_id} className="hover:bg-slate-50/80">
                                                <td className="py-2.5 px-3 font-bold text-blue-600">
                                                    <Link to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.agreement_reference)}`} className="hover:underline">
                                                        {row.agreement_reference}
                                                    </Link>
                                                </td>
                                                <td className="py-2.5 px-3">
                                                    <div className="font-semibold text-slate-800">{row.owner_name}</div>
                                                    <div className="text-[10px] text-slate-400 font-mono">{row.owner_code}</div>
                                                </td>
                                                <td className="py-2.5 px-3 font-semibold text-slate-800">{row.vehicle_number}</td>
                                                <td className="py-2.5 px-3 text-slate-600">
                                                    <span className="capitalize">{row.rental_basis}</span>
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                    {Number(row.base_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                    {(Number(row.mileage_payable) + Number(row.overtime_payable)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono font-semibold text-slate-900">
                                                    {Number(row.gross_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-rose-600">
                                                    {Number(row.deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono font-medium text-rose-700">
                                                    {Number(row.wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono font-bold text-emerald-800">
                                                    {Number(row.net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                            </tr>
                                        ))}
                                        {data.rows.length === 0 && (
                                            <tr>
                                                <td colSpan={10} className="py-8 text-center text-slate-400">
                                                    No owner settlement disbursements found for the selected period.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </Panel>
                    ) : (
                        /* WHT Ledger Statement (pmglled.frx) */
                        <Panel title="Ledger Account: Account Code :3305-000 WHT">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '12%' }}>Date</th>
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '15%' }}>Reference</th>
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '45%' }}>Description</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '9%' }}>Debit</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '10%' }}>Credit</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '9%' }}>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 font-mono">
                                        {(data.wht_ledger ?? []).map(entry => (
                                            <tr key={entry.reference} className="hover:bg-slate-50/80">
                                                <td className="py-2.5 px-3 text-slate-700">{entry.date}</td>
                                                <td className="py-2.5 px-3 font-bold text-blue-700">{entry.reference}</td>
                                                <td className="py-2.5 px-3 text-slate-800 font-sans font-medium">{entry.description}</td>
                                                <td className="py-2.5 px-3 text-right text-slate-500">{entry.debit || '-'}</td>
                                                <td className="py-2.5 px-3 text-right font-bold text-rose-700">{entry.credit}</td>
                                                <td className="py-2.5 px-3 text-right font-bold text-slate-900">{entry.balance}</td>
                                            </tr>
                                        ))}
                                        {(!data.wht_ledger || data.wht_ledger.length === 0) && (
                                            <tr>
                                                <td colSpan={6} className="py-8 text-center text-slate-400 font-sans">
                                                    No Withholding Tax ledger entries recorded for this period.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                    {data.wht_ledger && data.wht_ledger.length > 0 && (
                                        <tfoot>
                                            <tr className="border-t-2 border-slate-300 bg-slate-50 font-mono font-bold">
                                                <td colSpan={4} className="py-2.5 px-3 text-right font-sans">Total WHT Deducted:</td>
                                                <td className="py-2.5 px-3 text-right text-rose-700">-{Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td className="py-2.5 px-3 text-right text-slate-900">-{Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            </tr>
                                        </tfoot>
                                    )}
                                </table>
                            </div>
                        </Panel>
                    )}
                </>
            )}
        </div>
    );
}
