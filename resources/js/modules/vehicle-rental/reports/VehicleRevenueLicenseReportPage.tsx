/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError } from '@/shared/api/apiError';
import {
    exportRentalReport,
    fetchVehicleRevenueLicenseReport,
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
    const [busyExport, setBusyExport] = useState<'print' | 'pdf' | null>(null);

    const handleExport = async (format: 'print' | 'pdf') => {
        setBusyExport(format);
        const preview = format === 'print' ? window.open('', '_blank') : null;
        if (preview) {
            preview.document.title = 'Preparing report…';
            preview.document.body.textContent = 'Preparing report…';
        }
        try {
            await exportRentalReport('revenue-licenses', params as unknown as Record<string, unknown>, format, preview, `revenue-licenses.${format === 'pdf' ? 'pdf' : 'html'}`);
        } catch (e) {
            preview?.close();
            setError(e instanceof Error ? e.message : 'Export failed.');
        } finally {
            setBusyExport(null);
        }
    };

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
            <Panel title="Filter Expiration Dates">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <Input
                        type="date"
                        label="Expiry From Date (Optional)"
                        value={params.start_date || ''}
                        onChange={e => setParams(p => ({ ...p, start_date: e.target.value }))}
                    />
                    <Input
                        type="date"
                        label="Expiry To Date (Optional)"
                        value={params.end_date || ''}
                        onChange={e => setParams(p => ({ ...p, end_date: e.target.value }))}
                    />
                    <Select
                        label="Status Filter"
                        value={params.status}
                        onChange={e => setParams(p => ({ ...p, status: e.target.value as VehicleRevenueLicenseReportParams['status'] }))}
                        placeholder={null}
                        options={[
                            { value: 'all', label: 'All Fleet Licences' },
                            { value: 'expired', label: 'Expired Licences Only' },
                            { value: 'expiring_soon', label: 'Expiring Within 30 Days' },
                            { value: 'active', label: 'Valid Licences' },
                        ]}
                    />
                    <div className="flex items-end">
                        <Button
                            type="submit"
                            disabled={loading}
                            loading={loading}
                            className="w-full"
                        >
                            Apply Filters
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
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Fleet Checked</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_vehicles}</div>
                        </div>
                        <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Expired Licences</div>
                            <div className="mt-1 text-2xl font-bold text-rose-900">{data.summary.expired_count}</div>
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
                                                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
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
                                                            ? 'text-rose-700'
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
                                                    <span className="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800 border border-rose-200/80">
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
