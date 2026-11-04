import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import {
    fetchVehicleRevenueLicenseReport,
    getVehicleRevenueLicenseReportExportUrl,
    VehicleRevenueLicenseReportData,
    VehicleRevenueLicenseReportParams,
} from '../rentalReportsApi';

export default function VehicleRevenueLicenseReportPage() {
    const [params, setParams] = useState<VehicleRevenueLicenseReportParams>({
        start_date: '',
        end_date: '',
        status: 'all',
    });

    const [data, setData] = useState<VehicleRevenueLicenseReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: VehicleRevenueLicenseReportParams) => {
        setLoading(true);
        setError(null);
        fetchVehicleRevenueLicenseReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load revenue licence report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchVehicleRevenueLicenseReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load revenue licence report.';
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
                title="Vehicle Revenue Licence Expire Report"
                description="Regulatory compliance tracking for fleet revenue licences, expiration warnings, and renewal schedules."
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
                                    href={getVehicleRevenueLicenseReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getVehicleRevenueLicenseReportExportUrl(params, 'pdf')}
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
            <Panel title="Filter Expiration Dates">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Expiry From Date (Optional)</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date || ''}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Expiry To Date (Optional)</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.end_date || ''}
                            onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Status Filter</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.status}
                            onChange={e => setParams(p => ({ ...p, status: e.target.value as VehicleRevenueLicenseReportParams['status'] }))}
                        >
                            <option value="all">All Fleet Licences</option>
                            <option value="expired">Expired Licences Only</option>
                            <option value="expiring_soon">Expiring Within 30 Days</option>
                            <option value="active">Valid Licences</option>
                        </select>
                    </div>
                    <div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Filtering...' : 'Apply Filters'}
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
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Fleet Checked</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_vehicles}</div>
                        </div>
                        <div className="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-red-700">Expired Licences</div>
                            <div className="mt-1 text-2xl font-bold text-red-900">{data.summary.expired_count}</div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Expiring in 30 Days</div>
                            <div className="mt-1 text-2xl font-bold text-amber-900">{data.summary.expiring_soon_count}</div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Valid Licences</div>
                            <div className="mt-1 text-2xl font-bold text-emerald-900">{data.summary.active_count}</div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Vehicle Revenue Licence Status (${data.data.length} Records)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Vehicle No</th>
                                        <th className="py-2.5 px-3 font-semibold">Owner's Name</th>
                                        <th className="py-2.5 px-3 font-semibold">Ownership</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Expire Date</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Days Remaining</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.data.map(row => (
                                        <tr key={row.vehicle_id} className="hover:bg-slate-50/80">
                                            <td className="py-2.5 px-3 font-bold text-slate-900">{row.vehicle_no}</td>
                                            <td className="py-2.5 px-3 text-slate-700">{row.owner_name || '-'}</td>
                                            <td className="py-2.5 px-3">
                                                <span className={`inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium ${
                                                    row.ownership_type === 'Owner Leased'
                                                        ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                                        : 'bg-slate-100 text-slate-700'
                                                }`}>
                                                    {row.ownership_type}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-center font-medium text-slate-800">{row.expire_date}</td>
                                            <td className="py-2.5 px-3 text-center">
                                                {row.days_remaining !== null ? (
                                                    <span className={`font-semibold ${
                                                        row.days_remaining < 0
                                                            ? 'text-red-700'
                                                            : row.days_remaining <= 30
                                                            ? 'text-amber-700'
                                                            : 'text-emerald-700'
                                                    }`}>
                                                        {row.days_remaining < 0 ? `${Math.abs(row.days_remaining)} days ago` : `${row.days_remaining} days`}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400">-</span>
                                                )}
                                            </td>
                                            <td className="py-2.5 px-3 text-center">
                                                {row.status === 'expired' && (
                                                    <span className="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">
                                                        EXPIRED
                                                    </span>
                                                )}
                                                {row.status === 'expiring_soon' && (
                                                    <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                                        DUE SOON
                                                    </span>
                                                )}
                                                {row.status === 'active' && (
                                                    <span className="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                                                        VALID
                                                    </span>
                                                )}
                                                {row.status === 'missing' && (
                                                    <span className="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                                        MISSING
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {data.data.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="py-8 text-center text-slate-400">
                                                No vehicle revenue licences match the selected filter.
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
