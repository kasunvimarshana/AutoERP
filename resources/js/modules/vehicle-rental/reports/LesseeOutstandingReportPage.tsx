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
    fetchLesseeOutstandingReport,
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
    const [busyExport, setBusyExport] = useState<'print' | 'pdf' | null>(null);

    const handleExport = async (format: 'print' | 'pdf') => {
        setBusyExport(format);
        const preview = format === 'print' ? window.open('', '_blank') : null;
        if (preview) {
            preview.document.title = 'Preparing report…';
            preview.document.body.textContent = 'Preparing report…';
        }
        try {
            await exportRentalReport('lessee-outstanding', params as unknown as Record<string, unknown>, format, preview, `lessee-outstanding-${params.as_of_date || today}.${format === 'pdf' ? 'pdf' : 'html'}`);
        } catch (e) {
            preview?.close();
            setError(e instanceof Error ? e.message : 'Export failed.');
        } finally {
            setBusyExport(null);
        }
    };

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
            <Panel title="Report As-Of Date & Filter">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
                    <Input
                        type="date"
                        label="As-Of Date"
                        value={params.as_of_date || ''}
                        onChange={e => setParams(p => ({ ...p, as_of_date: e.target.value }))}
                        required
                    />
                    <Select
                        label="Aging Category"
                        value={params.aging_bucket}
                        onChange={e => setParams(p => ({ ...p, aging_bucket: e.target.value as LesseeOutstandingReportParams['aging_bucket'] }))}
                        options={[
                            { value: 'all', label: 'All Outstanding Invoices' },
                            { value: '0_30', label: 'Current (0 - 30 Days)' },
                            { value: '31_60', label: 'Overdue (31 - 60 Days)' },
                            { value: '61_90', label: 'Overdue (61 - 90 Days)' },
                            { value: '90_plus', label: 'Severely Overdue (> 90 Days)' },
                        ]}
                        placeholder={null}
                    />
                    <div className="flex items-end">
                        <Button
                            type="submit"
                            disabled={loading}
                            loading={loading}
                            className="w-full"
                        >
                            Recalculate Aging
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
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Active Lessees</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_lessees_count}</div>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Open Invoices</div>
                            <div className="mt-1 text-2xl font-bold text-blue-900">{data.summary.total_invoices_count}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-700">Total Invoiced</div>
                            <div className="mt-1 text-2xl font-bold text-slate-900">
                                {Number(data.summary.grand_total_original_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Total Outstanding</div>
                            <div className="mt-1 text-2xl font-bold text-rose-900">
                                {Number(data.summary.grand_total_balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {/* Grouped Tables per Lessee */}
                    <div className="space-y-4">
                        {(data.groups ?? []).length === 0 ? (
                            <Panel>
                                <div className="py-8 text-center text-sm text-slate-500">
                                    No outstanding lessee balances found for the selected as-of date.
                                </div>
                            </Panel>
                        ) : (
                            (data.groups ?? []).map(grp => (
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
                                                {(grp.invoices ?? []).map(inv => (
                                                    <tr key={inv.invoice_id} className="hover:bg-slate-50/60">
                                                        <td className="py-2 px-3 text-slate-600">{inv.txn_type}</td>
                                                        <td className="py-2 px-3 text-center text-slate-700">{inv.invoice_date}</td>
                                                        <td className="py-2 px-3 font-bold text-blue-600">
                                                            <Link to={`/invoices/${inv.invoice_id}`} className="hover:underline">
                                                                {inv.invoice_number}
                                                            </Link>
                                                        </td>
                                                        <td className="py-2 px-3 text-slate-700">{inv.agreement_duration}</td>
                                                        <td className="py-2 px-3 font-semibold text-slate-800">{inv.vehicle_no}</td>
                                                        <td className="py-2 px-3 text-right font-mono text-slate-700">
                                                            {Number(inv.original_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                        <td className="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                                            {Number(inv.balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                        <td className="py-2 px-3 text-center font-semibold">
                                                            <span className={inv.age_days > 90 ? 'text-rose-600 font-bold' : inv.age_days > 30 ? 'text-amber-600' : 'text-slate-700'}>
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
                                                    <td className="py-2.5 px-3 text-right font-mono text-rose-700">
                                                        {Number(grp.subtotal_balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ))
                        )}

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
