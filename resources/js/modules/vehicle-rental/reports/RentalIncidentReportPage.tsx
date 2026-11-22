/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    exportRentalReport,
    fetchRentalIncidentReport,
    RentalIncidentReportData,
    RentalIncidentReportParams,
} from '../rentalReportsApi';

export default function RentalIncidentReportPage() {
    const today = businessDateInputValue();
    const startOfYear = today.slice(0, 4) + '-01-01';

    const [params, setParams] = useState<RentalIncidentReportParams>({
        start_date: startOfYear,
        end_date: today,
        incident_type: '',
        status: '',
    });

    const [data, setData] = useState<RentalIncidentReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [busyExport, setBusyExport] = useState<'print' | 'pdf' | null>(null);

    const handleExport = async (format: 'print' | 'pdf') => {
        setBusyExport(format);
        const preview = format === 'print' ? window.open('', '_blank') : null;
        if (preview) {
            preview.document.title = 'Preparing report…';
            preview.document.body.textContent = 'Preparing report…';
        }
        try {
            await exportRentalReport('incidents-summary', params as unknown as Record<string, unknown>, format, preview, `incidents-summary-${params.start_date}-to-${params.end_date}.${format === 'pdf' ? 'pdf' : 'html'}`);
        } catch (e) {
            preview?.close();
            setError(e instanceof Error ? e.message : 'Export failed.');
        } finally {
            setBusyExport(null);
        }
    };

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
                        <LinkButton
                            to="/vehicle-rental/reports"
                            variant="secondary"
                            size="sm"
                        >
                            &larr; Reports Hub
                        </LinkButton>
                        {data && (
                            <>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    disabled={busyExport !== null}
                                    loading={busyExport === 'print'}
                                    onClick={() => void handleExport('print')}
                                >
                                    Print Preview
                                </Button>
                                <Button
                                    type="button"
                                    disabled={busyExport !== null}
                                    loading={busyExport === 'pdf'}
                                    onClick={() => void handleExport('pdf')}
                                >
                                    Download PDF
                                </Button>
                            </>
                        )}
                    </div>
                }
            />

            {/* Filter Parameter Form */}
            <Panel title="Incident Filter Criteria">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
                    <Input
                        type="date"
                        label="Occurred From"
                        value={params.start_date}
                        onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                        required
                    />
                    <Input
                        type="date"
                        label="Occurred Until"
                        value={params.end_date}
                        onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                        required
                    />
                    <Select
                        label="Incident Type"
                        value={params.incident_type || ''}
                        onChange={e => setParams(p => ({ ...p, incident_type: e.target.value }))}
                        placeholder="All Incident Types"
                        options={[
                            { value: 'accident', label: 'Accident' },
                            { value: 'damage', label: 'Damage' },
                            { value: 'theft', label: 'Theft' },
                            { value: 'violation', label: 'Violation' },
                            { value: 'breakdown', label: 'Breakdown' },
                            { value: 'other', label: 'Other' },
                        ]}
                    />
                    <Select
                        label="Review Status"
                        value={params.status || ''}
                        onChange={e => setParams(p => ({ ...p, status: e.target.value }))}
                        placeholder="All Statuses"
                        options={[
                            { value: 'recorded', label: 'Recorded / Under Review' },
                            { value: 'confirmed', label: 'Confirmed Risk' },
                            { value: 'rejected', label: 'Rejected Claim' },
                        ]}
                    />
                    <div className="flex items-end">
                        <Button
                            type="submit"
                            disabled={loading}
                            loading={loading}
                            className="w-full"
                        >
                            Filter Incidents
                        </Button>
                    </div>
                </form>
            </Panel>

            <ErrorAlert error={error ? toApiError(error) : null} inline />

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
                        <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Confirmed Liability</div>
                            <div className="mt-1 text-2xl font-bold text-rose-900">{data.summary.confirmed_count}</div>
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
                                                <Link to={`/vehicle-rental/incidents?search=${encodeURIComponent(row.reference)}`} className="hover:underline">
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
                                                        ? 'bg-rose-100 text-rose-800 border border-rose-200/80'
                                                        : row.status === 'rejected'
                                                        ? 'bg-slate-100 text-slate-700 border border-slate-200/80'
                                                        : 'bg-amber-100 text-amber-800 border border-amber-200/80'
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
