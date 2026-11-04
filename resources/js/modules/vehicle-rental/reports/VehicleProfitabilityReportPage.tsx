import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    fetchVehicleProfitabilityReport,
    getVehicleProfitabilityReportExportUrl,
    VehicleProfitabilityReportData,
    VehicleProfitabilityReportParams,
} from '../rentalReportsApi';

export default function VehicleProfitabilityReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<VehicleProfitabilityReportParams>({
        start_date: startOfMonth,
        end_date: today,
        ownership_type: 'all',
    });

    const [data, setData] = useState<VehicleProfitabilityReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: VehicleProfitabilityReportParams) => {
        setLoading(true);
        setError(null);
        fetchVehicleProfitabilityReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load vehicle profitability report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchVehicleProfitabilityReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load vehicle profitability report.';
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
                title="Fleet Vehicle Profitability & Unit Economics Report"
                description="Vehicle-level profit & loss, direct operating costs (owner lease, fuel, maintenance, tolls), gross margins, and per-kilometer economics."
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
                                    href={getVehicleProfitabilityReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getVehicleProfitabilityReportExportUrl(params, 'pdf')}
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
            <Panel title="Report Period & Scope">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Period From</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Period Until</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.end_date}
                            onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Ownership Scope</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.ownership_type}
                            onChange={e => setParams(p => ({ ...p, ownership_type: e.target.value as VehicleProfitabilityReportParams['ownership_type'] }))}
                        >
                            <option value="all">Entire Fleet (Company & Leased)</option>
                            <option value="company">Company Owned Fleet Only</option>
                            <option value="owner_leased">Owner Leased Fleet Only</option>
                        </select>
                    </div>
                    <div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Evaluating Unit Economics...' : 'Calculate Profitability'}
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
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Fleet Revenue</div>
                            <div className="mt-1 text-xl font-bold text-blue-900">
                                {Number(data.summary.fleet_total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-red-700">Direct Costs</div>
                            <div className="mt-1 text-xl font-bold text-red-900">
                                {Number(data.summary.fleet_total_direct_costs).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Gross Margin</div>
                            <div className="mt-1 text-xl font-bold text-emerald-900">
                                {Number(data.summary.fleet_gross_margin).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Margin %</div>
                            <div className="mt-1 text-2xl font-bold text-purple-900">{data.summary.fleet_margin_percentage}%</div>
                        </div>
                        <div className="rounded-xl border border-cyan-200 bg-cyan-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-cyan-700">Distance Driven</div>
                            <div className="mt-1 text-2xl font-bold text-cyan-900">{data.summary.fleet_total_distance_km.toLocaleString()} km</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fleet Evaluated</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_vehicles_evaluated}</div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Fleet Unit Economics Breakdown (${data.rows.length} Vehicles)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                        <th className="py-2.5 px-3 font-semibold">Ownership</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Revenue</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Owner Lease</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Fuel</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Maint.</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Toll/Other</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Direct Costs</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Gross Margin</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Margin %</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Distance</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">Cost/KM</th>
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
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-800">
                                                {Number(row.customer_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                                                {Number(row.owner_lease_cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                                                {Number(row.fuel_cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                                                {Number(row.maintenance_cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                                                {(Number(row.toll_cost) + Number(row.other_cost)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono font-medium text-slate-900">
                                                {Number(row.total_direct_costs).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-mono font-bold">
                                                <span className={Number(row.gross_margin) >= 0 ? 'text-emerald-700' : 'text-red-700'}>
                                                    {Number(row.gross_margin).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-semibold">
                                                <span className={row.margin_percentage >= 0 ? 'text-emerald-700' : 'text-red-700'}>
                                                    {row.margin_percentage}%
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-right text-slate-700">{row.distance_km.toLocaleString()} km</td>
                                            <td className="py-2.5 px-3 text-right font-mono text-slate-600">{row.cost_per_km}</td>
                                        </tr>
                                    ))}
                                    {data.rows.length === 0 && (
                                        <tr>
                                            <td colSpan={12} className="py-8 text-center text-slate-400">
                                                No vehicle profitability records match the selected scope.
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
