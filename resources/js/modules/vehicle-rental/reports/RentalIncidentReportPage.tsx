import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import {
    fetchRentalIncidentReport,
    getRentalIncidentReportExportUrl,
    RentalIncidentReportData,
    RentalIncidentReportParams,
} from '../rentalReportsApi';

export default function RentalIncidentReportPage() {
    const today = new Date().toISOString().slice(0, 10);
    const startOfYear = new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0, 10);

    const [params, setParams] = useState<RentalIncidentReportParams>({
        start_date: startOfYear,
        end_date: today,
        incident_type: '',
        status: '',
    });

    const [data, setData] = useState<RentalIncidentReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    const executeFetch = (fetchParams: RentalIncidentReportParams) => {
        setLoading(true);
        setError(null);
        fetchRentalIncidentReport(fetchParams)
            .then(res => setData(res))
            .catch(err => {
                const msg = err?.response?.data?.message || err?.message || 'Failed to load rental incident report.';
                setError(msg);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        let active = true;
        fetchRentalIncidentReport(params)
            .then(res => {
                if (active) setData(res);
            })
            .catch(err => {
                if (active) {
                    const msg = err?.response?.data?.message || err?.message || 'Failed to load rental incident report.';
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
                title="Rental Incident & Damage Risk Report"
                description="Fleet risk management register, damage event classifications, liability determinations, and photographic evidence audits."
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
                                    href={getRentalIncidentReportExportUrl(params, 'print')}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    Print Preview
                                </a>
                                <a
                                    href={getRentalIncidentReportExportUrl(params, 'pdf')}
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
            <Panel title="Incident Filter Criteria">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Occurred From</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.start_date}
                            onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Occurred Until</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.end_date}
                            onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Incident Type</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.incident_type || ''}
                            onChange={e => setParams(p => ({ ...p, incident_type: e.target.value }))}
                        >
                            <option value="">All Incident Types</option>
                            <option value="accident">Accident</option>
                            <option value="damage">Damage</option>
                            <option value="theft">Theft</option>
                            <option value="violation">Violation</option>
                            <option value="breakdown">Breakdown</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Review Status</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.status || ''}
                            onChange={e => setParams(p => ({ ...p, status: e.target.value }))}
                        >
                            <option value="">All Statuses</option>
                            <option value="recorded">Recorded / Under Review</option>
                            <option value="confirmed">Confirmed Risk</option>
                            <option value="rejected">Rejected Claim</option>
                        </select>
                    </div>
                    <div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Searching...' : 'Filter Incidents'}
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
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Incidents</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_incidents}</div>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Under Review</div>
                            <div className="mt-1 text-2xl font-bold text-amber-900">{data.summary.recorded_count}</div>
                        </div>
                        <div className="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-red-700">Confirmed Liability</div>
                            <div className="mt-1 text-2xl font-bold text-red-900">{data.summary.confirmed_count}</div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Rejected Claims</div>
                            <div className="mt-1 text-2xl font-bold text-emerald-900">{data.summary.rejected_count}</div>
                        </div>
                    </div>

                    {/* Report Table */}
                    <Panel title={`Fleet Incident Risk Register (${data.rows.length} Events)`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <th className="py-2.5 px-3 font-semibold">Incident Ref</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Occurred On</th>
                                        <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                        <th className="py-2.5 px-3 font-semibold">Customer</th>
                                        <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                        <th className="py-2.5 px-3 font-semibold">Type</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Status</th>
                                        <th className="py-2.5 px-3 font-semibold">Description</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">Attachments</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.rows.map(row => (
                                        <tr key={row.incident_id} className="hover:bg-slate-50/80">
                                            <td className="py-2.5 px-3 font-bold text-blue-600">
                                                <Link to={`/vehicle-rental/incidents`} className="hover:underline">
                                                    {row.reference}
                                                </Link>
                                            </td>
                                            <td className="py-2.5 px-3 text-center text-slate-700">{row.occurred_on}</td>
                                            <td className="py-2.5 px-3 font-semibold text-slate-900">{row.vehicle_number}</td>
                                            <td className="py-2.5 px-3 text-slate-800">{row.customer_name}</td>
                                            <td className="py-2.5 px-3 text-slate-600 font-mono text-[11px]">{row.agreement_reference}</td>
                                            <td className="py-2.5 px-3">
                                                <span className="capitalize font-medium text-slate-700">{row.incident_type}</span>
                                            </td>
                                            <td className="py-2.5 px-3 text-center">
                                                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                    row.status === 'confirmed'
                                                        ? 'bg-red-100 text-red-800'
                                                        : row.status === 'rejected'
                                                        ? 'bg-slate-100 text-slate-700'
                                                        : 'bg-amber-100 text-amber-800'
                                                }`}>
                                                    {row.status.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-slate-600 max-w-xs truncate" title={row.description}>
                                                {row.description}
                                            </td>
                                            <td className="py-2.5 px-3 text-center font-semibold text-slate-700">
                                                {row.attachment_count > 0 ? (
                                                    <span className="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-blue-700 border border-blue-200">
                                                        {row.attachment_count} files
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400">-</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {data.rows.length === 0 && (
                                        <tr>
                                            <td colSpan={9} className="py-8 text-center text-slate-400">
                                                No vehicle rental incident records match the filter criteria.
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
