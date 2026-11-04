import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    fetchRentalRevenueReport,
    getRentalRevenueReportExportUrl,
    RentalRevenueReportData,
    RentalRevenueReportParams,
} from '../rentalReportsApi';

export default function RentalRevenueReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<RentalRevenueReportParams>({
        start_date: startOfMonth,
        end_date: today,
    });

    const [data, setData] = useState<RentalRevenueReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: RentalRevenueReportParams) => {
        setLoading(true);
        setError(null);
        fetchRentalRevenueReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load rental revenue report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchRentalRevenueReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load rental revenue report.';
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
                title="Rental Revenue & Commercial Billing Summary"
                description="Aggregated commercial performance across customer contracts, base rent cycles, excess distance, overtime, and toll reimbursements."
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
                                    href={getRentalRevenueReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getRentalRevenueReportExportUrl(params, 'pdf')}
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
            <Panel title="Report Date Range">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Billing Period From</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Billing Period Until</label>
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
                            {loading ? 'Compiling Revenue...' : 'Generate Revenue Summary'}
                        </button>
                    </div>
                </form>
            </Panel>

            {error && (
                <div className="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-2xs">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* KPI Metrics */}
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Billed Agreements</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_agreements}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Base Rent</div>
                            <div className="mt-1 text-xl font-bold text-blue-900">
                                {Number(data.summary.total_base_rent).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-700">Excess Distance</div>
                            <div className="mt-1 text-xl font-bold text-slate-900">
                                {Number(data.summary.total_excess_mileage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Overtime & Night</div>
                            <div className="mt-1 text-xl font-bold text-amber-900">
                                {(Number(data.summary.total_overtime) + Number(data.summary.total_night_out)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-700">Tolls / Passes</div>
                            <div className="mt-1 text-xl font-bold text-slate-900">
                                {Number(data.summary.total_toll_pass).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Net Revenue</div>
                            <div className="mt-1 text-xl font-bold text-emerald-900">
                                {Number(data.summary.grand_total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Customer Agreements Revenue Breakdown (${data.rows.length} Agreements)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                        <th className="py-2.5 px-3 font-semibold">Customer</th>
                                        <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                        <th className="py-2.5 px-3 font-semibold">Basis / Mode</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Base Rent</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Excess KM</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Overtime</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Night Out</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Toll / Pass</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Total Billed</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.rows.map(row => (
                                        <tr key={row.agreement_id} className="hover:bg-slate-50/80">
                                            <td className="py-2.5 px-3 font-bold text-blue-600">
                                                <Link to={`/vehicle-rental/customer/agreements/${row.agreement_id}`} className="hover:underline">
                                                    {row.agreement_reference}
                                                </Link>
                                            </td>
                                            <td className="py-2.5 px-3">
                                                <div className="font-semibold text-slate-800">{row.customer_name}</div>
                                                <div className="text-[10px] text-slate-400 font-mono">{row.customer_code}</div>
                                            </td>
                                            <td className="py-2.5 px-3 font-semibold text-slate-800">{row.vehicle_number}</td>
                                            <td className="py-2.5 px-3 text-slate-600">
                                                <span className="capitalize">{row.rental_basis}</span>
                                                <span className="text-slate-400"> / </span>
                                                <span className="capitalize">{row.driver_mode.replace('_', ' ')}</span>
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.base_rent).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.excess_mileage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.overtime).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.night_out).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">
                                                {Number(row.toll_pass).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                                {Number(row.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                        </tr>
                                    ))}
                                    {data.rows.length === 0 && (
                                        <tr>
                                            <td colSpan={10} className="py-8 text-center text-slate-400">
                                                No commercial rental revenue transactions recorded in this period.
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
