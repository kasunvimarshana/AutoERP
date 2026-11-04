import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    fetchFleetUtilizationReport,
    getFleetUtilizationReportExportUrl,
    FleetUtilizationReportData,
    FleetUtilizationReportParams,
} from '../rentalReportsApi';

export default function FleetUtilizationReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<FleetUtilizationReportParams>({
        start_date: startOfMonth,
        end_date: today,
        ownership_type: 'all',
        custody_status: 'all',
    });

    const [data, setData] = useState<FleetUtilizationReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: FleetUtilizationReportParams) => {
        setLoading(true);
        setError(null);
        fetchFleetUtilizationReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load fleet utilization report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchFleetUtilizationReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load fleet utilization report.';
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
                title="Fleet Utilization & Vehicle Status Report"
                description="Comprehensive physical fleet operational custody, period kilometer distance, and asset utilization analysis."
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
                                    href={getFleetUtilizationReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getFleetUtilizationReportExportUrl(params, 'pdf')}
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
            <Panel title="Report Parameters">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">End Date</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.end_date}
                            onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Ownership Type</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.ownership_type}
                            onChange={e => setParams(p => ({ ...p, ownership_type: e.target.value as FleetUtilizationReportParams['ownership_type'] }))}
                        >
                            <option value="all">All Ownerships</option>
                            <option value="company">Company Owned</option>
                            <option value="owner_leased">Owner Leased</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Custody Status</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.custody_status}
                            onChange={e => setParams(p => ({ ...p, custody_status: e.target.value as FleetUtilizationReportParams['custody_status'] }))}
                        >
                            <option value="all">All Statuses</option>
                            <option value="in_custody">In Custody</option>
                            <option value="available">Available / Idle</option>
                            <option value="planned">Planned</option>
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={loading}
                            className="flex-1 rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Generating...' : 'Generate Report'}
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
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Fleet</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_vehicles}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">In Custody</div>
                            <div className="mt-1 text-2xl font-bold text-blue-900">{data.summary.in_custody_count}</div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Available / Idle</div>
                            <div className="mt-1 text-2xl font-bold text-emerald-900">{data.summary.available_count}</div>
                        </div>
                        <div className="rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Average Utilization</div>
                            <div className="mt-1 text-2xl font-bold text-purple-900">{data.summary.average_utilization_rate}%</div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Total KM Driven</div>
                            <div className="mt-1 text-2xl font-bold text-amber-900">{data.summary.total_distance_km.toLocaleString()}</div>
                        </div>
                        <div className="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">Owner Leased</div>
                            <div className="mt-1 text-2xl font-bold text-indigo-900">{data.summary.owner_leased_count}</div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Fleet Custody & Utilization (${data.rows.length} Vehicles)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                        <th className="py-2.5 px-3 font-semibold">Ownership</th>
                                        <th className="py-2.5 px-3 font-semibold">Owner / Supplier</th>
                                        <th className="py-2.5 px-3 font-semibold">Custody Status</th>
                                        <th className="py-2.5 px-3 font-semibold">Current Customer</th>
                                        <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                        <th className="py-2.5 px-3 font-semibold">Driver</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Odometer</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Period KM</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Custody Days</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Utilization %</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.rows.map(row => (
                                        <tr key={row.vehicle_id} className="hover:bg-slate-50/80">
                                            <td className="py-2.5 px-3 font-semibold text-slate-900">
                                                {row.registration_number}
                                                <div className="text-[10px] text-slate-400 font-normal">{row.vehicle_make_model}</div>
                                            </td>
                                            <td className="py-2.5 px-3">
                                                <span className={`inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium ${
                                                    row.ownership_type === 'Owner Leased'
                                                        ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                                        : 'bg-slate-100 text-slate-700'
                                                }`}>
                                                    {row.ownership_type}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-slate-700">{row.owner_name}</td>
                                            <td className="py-2.5 px-3">
                                                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ${
                                                    row.status_key === 'in_custody'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : row.status_key === 'planned'
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-emerald-100 text-emerald-800'
                                                }`}>
                                                    {row.custody_status}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-slate-800 font-medium">{row.current_customer}</td>
                                            <td className="py-2.5 px-3 text-slate-600">{row.current_agreement}</td>
                                            <td className="py-2.5 px-3 text-slate-600">{row.current_driver}</td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-700">{row.current_odometer}</td>
                                            <td className="py-2.5 px-3 text-right font-semibold text-slate-900">{row.period_distance_km.toLocaleString()}</td>
                                            <td className="py-2.5 px-3 text-right text-slate-700">{row.days_in_custody}</td>
                                            <td className="py-2.5 px-3 text-right">
                                                <div className="font-semibold text-slate-900">{row.utilization_rate}%</div>
                                                <div className="h-1.5 w-16 bg-slate-200 rounded-full ml-auto mt-0.5 overflow-hidden">
                                                    <div
                                                        className="h-full bg-blue-600 rounded-full"
                                                        style={{ width: `${Math.min(100, row.utilization_rate)}%` }}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {data.rows.length === 0 && (
                                        <tr>
                                            <td colSpan={11} className="py-8 text-center text-slate-400">
                                                No fleet vehicles match the selected criteria.
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
