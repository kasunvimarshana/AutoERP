import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import {
    fetchOwnerDisbursementReport,
    getOwnerDisbursementReportExportUrl,
    OwnerDisbursementReportData,
    OwnerDisbursementReportParams,
} from '../rentalReportsApi';

export default function OwnerDisbursementReportPage() {
    const today = new Date().toISOString().slice(0, 10);
    const startOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);

    const [params, setParams] = useState<OwnerDisbursementReportParams>({
        start_date: startOfMonth,
        end_date: today,
    });

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
        fetchOwnerDisbursementReport(params)
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
    }, []);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeFetch(params);
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
                                    href={getOwnerDisbursementReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getOwnerDisbursementReportExportUrl(params, 'pdf')}
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
            <Panel title="Settlement Period">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
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

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* KPI Metrics */}
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Owner Agreements</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_owner_agreements}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Base Lease Pay</div>
                            <div className="mt-1 text-xl font-bold text-blue-900">
                                {Number(data.summary.total_base_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Mileage Share</div>
                            <div className="mt-1 text-xl font-bold text-purple-900">
                                {Number(data.summary.total_mileage_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Overtime Share</div>
                            <div className="mt-1 text-xl font-bold text-amber-900">
                                {Number(data.summary.total_overtime_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-red-700">Deductions & WHT</div>
                            <div className="mt-1 text-xl font-bold text-red-900">
                                {Number(data.summary.total_deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Net Payable</div>
                            <div className="mt-1 text-xl font-bold text-emerald-900">
                                {Number(data.summary.total_net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Owner Settlements & Disbursements (${data.rows.length} Contracts)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                        <th className="py-2.5 px-3 font-semibold">Vehicle Owner / Supplier</th>
                                        <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                        <th className="py-2.5 px-3 font-semibold">Basis</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Base Entitlement</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Mileage Share</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Overtime Share</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Deductions</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Net Payable</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.rows.map(row => (
                                        <tr key={row.owner_agreement_id} className="hover:bg-slate-50/80">
                                            <td className="py-2.5 px-3 font-bold text-blue-600">
                                                <Link to={`/vehicle-rental/owner/agreements/${row.owner_agreement_id}`} className="hover:underline">
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
                                                {Number(row.mileage_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.overtime_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-red-600">
                                                {Number(row.deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono font-bold text-emerald-800">
                                                {Number(row.net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                        </tr>
                                    ))}
                                    {data.rows.length === 0 && (
                                        <tr>
                                            <td colSpan={9} className="py-8 text-center text-slate-400">
                                                No owner settlement disbursements found for the selected period.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                </>
            )}
        </div>
    );
}
