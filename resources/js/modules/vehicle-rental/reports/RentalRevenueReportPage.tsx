import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    exportRentalReport,
    fetchRentalRevenueReport,
    RentalRevenueReportData,
    RentalRevenueReportParams,
} from '../rentalReportsApi';

type RevenueTab = 'summary' | 'invoice_listing';

export default function RentalRevenueReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<RentalRevenueReportParams>({
        start_date: startOfMonth,
        end_date: today,
        report_type: 'summary',
    });

    const [activeTab, setActiveTab] = useState<RevenueTab>('summary');
    const [data, setData] = useState<RentalRevenueReportData | null>(null);
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
            await exportRentalReport(
                'rental-revenue',
                currentExportParams as unknown as Record<string, unknown>,
                format,
                preview,
                `rental-revenue-${currentExportParams.start_date}-to-${currentExportParams.end_date}.${format === 'pdf' ? 'pdf' : 'html'}`
            );
        } catch (e) {
            preview?.close();
            setError(e instanceof Error ? e.message : 'Export failed.');
        } finally {
            setBusyExport(null);
        }
    };

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
        fetchRentalRevenueReport({ ...params, report_type: activeTab })
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
    }, [activeTab]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeFetch({ ...params, report_type: activeTab });
    };

    const currentExportParams: RentalRevenueReportParams = {
        ...params,
        report_type: activeTab,
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Rental Revenue & Commercial Billing Summary"
                description="Aggregated commercial performance across customer contracts, base rent cycles, excess distance, overtime, toll reimbursements, and all-invoice billing registers."
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
                            {loading ? 'Compiling Report...' : 'Generate Revenue Summary'}
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
                    Commercial Revenue Summary (By Agreement)
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('invoice_listing')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 transition-colors ${
                        activeTab === 'invoice_listing'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Lessee's All Invoice Listing (Summary)
                </button>
            </div>

            {error && (
                <div className="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-2xs">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* View 1: Commercial Revenue Summary */}
                    {activeTab === 'summary' && (
                        <>
                            {/* KPI Metrics */}
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Agreements</div>
                                    <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_agreements}</div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Base Rent</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(data.summary.total_base_rent).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Excess Distance</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(data.summary.total_excess_mileage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Overtime & Night</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {(Number(data.summary.total_overtime) + Number(data.summary.total_night_out)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Tolls / Passes</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(data.summary.total_toll_pass).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Gross Billed</div>
                                    <div className="mt-1 text-lg font-bold text-blue-900">
                                        {Number(data.summary.grand_total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                            </div>

                            {/* Revenue by Agreement Table */}
                            <Panel title={`Customer Agreements (${data.rows.length} Active in Period)`}>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                                <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                                <th className="py-2.5 px-3 font-semibold">Customer / Lessee</th>
                                                <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                                <th className="py-2.5 px-3 font-semibold">Basis</th>
                                                <th className="py-2.5 px-3 font-semibold">Mode</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Base Rent</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Excess KM</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Overtime</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Night Out</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Toll Pass</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Total Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {data.rows.map(row => (
                                                <tr key={row.agreement_id} className="hover:bg-slate-50/80">
                                                    <td className="py-2.5 px-3 font-bold text-blue-600">
                                                        <Link to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(row.agreement_reference)}`} className="hover:underline">
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
                                                    </td>
                                                    <td className="py-2.5 px-3 text-slate-600">
                                                        <span className="capitalize">{row.driver_mode}</span>
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
                                                    <td colSpan={11} className="py-8 text-center text-slate-400">
                                                        No customer agreements found for the selected billing period.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                        {data.rows.length > 0 && (
                                            <tfoot>
                                                <tr className="border-t-2 border-slate-300 bg-slate-50 font-mono font-bold">
                                                    <td colSpan={5} className="py-2.5 px-3 text-right font-sans">Period Totals:</td>
                                                    <td className="py-2.5 px-3 text-right">{Number(data.summary.total_base_rent).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td className="py-2.5 px-3 text-right">{Number(data.summary.total_excess_mileage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td className="py-2.5 px-3 text-right">{Number(data.summary.total_overtime).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td className="py-2.5 px-3 text-right">{Number(data.summary.total_night_out).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td className="py-2.5 px-3 text-right">{Number(data.summary.total_toll_pass).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td className="py-2.5 px-3 text-right text-blue-900">{Number(data.summary.grand_total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </div>
                            </Panel>
                        </>
                    )}

                    {/* View 2: Lessee's All Invoice Listing (Summary) - matching legacy FoxPro prndebinv3_s.pdf */}
                    {activeTab === 'invoice_listing' && (
                        <>
                            {/* KPI Metrics */}
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Invoices Count</div>
                                    <div className="mt-1 text-2xl font-bold text-slate-900">{data.summary.total_invoices_count ?? (data.invoices?.length ?? 0)}</div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Rental Charges</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(data.summary.invoice_total_rental_charge ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Excess KM Charges</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(data.summary.invoice_total_excess_km ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-amber-700">VAT (18%)</div>
                                    <div className="mt-1 text-lg font-bold text-amber-900">
                                        {Number(data.summary.invoice_total_vat ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Total Invoiced</div>
                                    <div className="mt-1 text-lg font-bold text-blue-900">
                                        {Number(data.summary.invoice_grand_total ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                            </div>

                            <Panel title="Lessee's All Invoice Listing (Summary) Register">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border border-slate-200">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 text-slate-700">
                                                <th className="py-2.5 px-3 font-semibold" style={{ width: '13%' }}>Invoice No</th>
                                                <th className="py-2.5 px-3 font-semibold text-center" style={{ width: '10%' }}>Invoice Date</th>
                                                <th className="py-2.5 px-3 font-semibold" style={{ width: '35%' }}>Lessee's Details & Period</th>
                                                <th className="py-2.5 px-3 font-semibold text-center" style={{ width: '12%' }}>Vehicle No</th>
                                                <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '10%' }}>Rental Charge</th>
                                                <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '10%' }}>Excess KM</th>
                                                <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '10%' }}>VAT</th>
                                                <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '12%' }}>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 font-mono">
                                            {(data.invoices ?? []).map(inv => (
                                                <tr key={inv.invoice_number} className="hover:bg-slate-50/80">
                                                    <td className="py-2 px-3 font-bold text-blue-600">{inv.invoice_number}</td>
                                                    <td className="py-2 px-3 text-center text-slate-600">{inv.invoice_date}</td>
                                                    <td className="py-2 px-3 font-sans font-medium text-slate-800">{inv.customer_details}</td>
                                                    <td className="py-2 px-3 text-center font-bold text-slate-800">{inv.vehicle_number}</td>
                                                    <td className="py-2 px-3 text-right text-slate-700">{inv.rental_charge}</td>
                                                    <td className="py-2 px-3 text-right text-slate-700">{inv.excess_km_charge}</td>
                                                    <td className="py-2 px-3 text-right text-slate-700">{inv.vat_amount}</td>
                                                    <td className="py-2 px-3 text-right font-bold text-slate-900">{inv.total_amount}</td>
                                                </tr>
                                            ))}
                                            {(!data.invoices || data.invoices.length === 0) && (
                                                <tr>
                                                    <td colSpan={8} className="py-8 text-center text-slate-400 font-sans">
                                                        No customer invoice billing records found for this period.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                        {data.invoices && data.invoices.length > 0 && (
                                            <tfoot>
                                                <tr className="border-t-2 border-slate-300 bg-slate-50 font-mono font-bold">
                                                    <td colSpan={4} className="py-2.5 px-3 text-right font-sans">Total Invoiced:</td>
                                                    <td className="py-2.5 px-3 text-right">{data.summary.invoice_total_rental_charge}</td>
                                                    <td className="py-2.5 px-3 text-right">{data.summary.invoice_total_excess_km}</td>
                                                    <td className="py-2.5 px-3 text-right">{data.summary.invoice_total_vat}</td>
                                                    <td className="py-2.5 px-3 text-right text-blue-900">{data.summary.invoice_grand_total}</td>
                                                </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </div>
                            </Panel>
                        </>
                    )}
                </>
            )}
        </div>
    );
}
