import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    exportRentalReport,
    fetchOwnerDisbursementReport,
    OwnerDisbursementReportData,
    OwnerDisbursementReportParams,
} from '../rentalReportsApi';
import { OwnerSettlementModal } from '../OwnerSettlementModal';

type OwnerReportTab = 'summary' | 'statement_detail_1' | 'statement_detail_2' | 'wht_ledger';

export default function OwnerDisbursementReportPage() {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [params, setParams] = useState<OwnerDisbursementReportParams>({
        start_date: startOfMonth,
        end_date: today,
        wht_rate: 10,
        report_type: 'summary',
    });

    const [activeTab, setActiveTab] = useState<OwnerReportTab>('summary');
    const [data, setData] = useState<OwnerDisbursementReportData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [busyExport, setBusyExport] = useState<'print' | 'pdf' | null>(null);
    const [showSettlementModal, setShowSettlementModal] = useState<boolean>(false);

    const handleExport = async (format: 'print' | 'pdf') => {
        setBusyExport(format);
        const preview = format === 'print' ? window.open('', '_blank') : null;
        if (preview) {
            preview.document.title = 'Preparing report…';
            preview.document.body.textContent = 'Preparing report…';
        }
        try {
            await exportRentalReport(
                'owner-disbursements',
                currentExportParams as unknown as Record<string, unknown>,
                format,
                preview,
                `owner-disbursements-${currentExportParams.start_date}-to-${currentExportParams.end_date}.${format === 'pdf' ? 'pdf' : 'html'}`
            );
        } catch (e) {
            preview?.close();
            setError(e instanceof Error ? e.message : 'Export failed.');
        } finally {
            setBusyExport(null);
        }
    };

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
        fetchOwnerDisbursementReport({ ...params, report_type: activeTab })
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
    }, [activeTab]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeFetch({ ...params, report_type: activeTab });
    };

    const currentExportParams: OwnerDisbursementReportParams = {
        ...params,
        report_type: activeTab,
    };

    const statement = data?.statement;

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Owner Disbursement & Payables Ledger"
                description="Lessor settlements, monthly base lease entitlements, vehicle-wise statement of accounts (Detail I & II), repair chargebacks, and withholding tax (WHT) ledger statements."
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
                                {statement?.owner_agreement_id && (
                                    <Button
                                        type="button"
                                        variant="primary"
                                        onClick={() => setShowSettlementModal(true)}
                                    >
                                        Generate Inbound AP Bill
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                }
            />

            {/* Filter Parameter Form */}
            <Panel title="Settlement Period & Vehicle Focus">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
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
                        <label className="block text-xs font-medium text-slate-700 mb-1">Owner Contract / Vehicle Focus</label>
                        <select
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                            value={params.owner_agreement_id ?? ''}
                            onChange={e => setParams(p => ({ ...p, owner_agreement_id: e.target.value ? Number(e.target.value) : undefined }))}
                        >
                            <option value="">All Fleet Contracts (Summary)</option>
                            {data?.rows.map(r => (
                                <option key={r.owner_agreement_id} value={r.owner_agreement_id}>
                                    {r.agreement_reference} &mdash; {r.vehicle_number} ({r.owner_name})
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">Withholding Tax (WHT %)</label>
                        <div className="relative">
                            <input
                                type="number"
                                step="0.5"
                                min="0"
                                max="100"
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:outline-none pr-7"
                                value={params.wht_rate ?? 10}
                                onChange={e => setParams(p => ({ ...p, wht_rate: Number(e.target.value) }))}
                            />
                            <span className="absolute right-2.5 top-2 text-xs font-semibold text-slate-400">%</span>
                        </div>
                    </div>
                    <div className="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button
                            type="submit"
                            disabled={loading}
                            className="rounded-md bg-blue-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Compiling Report...' : 'Apply Filters & Compile'}
                        </button>
                    </div>
                </form>
            </Panel>

            {/* View Mode Tabs */}
            <div className="flex border-b border-slate-200 overflow-x-auto">
                <button
                    type="button"
                    onClick={() => setActiveTab('summary')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 whitespace-nowrap transition-colors ${
                        activeTab === 'summary'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Owner Settlements Summary
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('statement_detail_1')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 whitespace-nowrap transition-colors ${
                        activeTab === 'statement_detail_1'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Statement of Accounts (Detail I)
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('statement_detail_2')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 whitespace-nowrap transition-colors ${
                        activeTab === 'statement_detail_2'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Statement of Accounts (Detail II)
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('wht_ledger')}
                    className={`px-4 py-2 text-xs font-semibold border-b-2 whitespace-nowrap transition-colors ${
                        activeTab === 'wht_ledger'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                >
                    Withholding Tax (WHT) Ledger (:3305-000 WHT)
                </button>
            </div>

            {error && (
                <div className="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-2xs">
                    {error}
                </div>
            )}

            {data && (
                <>
                    {/* View 1: Standard Owner Settlements Table */}
                    {activeTab === 'summary' && (
                        <>
                            {/* KPI Metrics */}
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                                <div className="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Agreements</div>
                                    <div className="mt-1 text-xl font-bold text-slate-900">{data.summary.total_owner_agreements}</div>
                                </div>
                                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Base Pay</div>
                                    <div className="mt-1 text-lg font-bold text-blue-900">
                                        {Number(data.summary.total_base_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-700">Usage Share</div>
                                    <div className="mt-1 text-lg font-bold text-slate-900">
                                        {(Number(data.summary.total_mileage_payable) + Number(data.summary.total_overtime_payable)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Gross Payable</div>
                                    <div className="mt-1 text-lg font-bold text-blue-900">
                                        {Number(data.summary.total_gross_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-amber-700">Deductions</div>
                                    <div className="mt-1 text-lg font-bold text-amber-900">
                                        {Number(data.summary.total_deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-rose-700">WHT ({params.wht_rate ?? 10}%)</div>
                                    <div className="mt-1 text-lg font-bold text-rose-900">
                                        {Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 shadow-sm">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Net Payable</div>
                                    <div className="mt-1 text-lg font-bold text-emerald-900">
                                        {Number(data.summary.total_net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </div>
                            </div>

                            <Panel title={`Owner Settlements & Disbursements (${data.rows.length} Contracts)`}>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                                <th className="py-2.5 px-3 font-semibold">Agreement</th>
                                                <th className="py-2.5 px-3 font-semibold">Vehicle Owner / Supplier</th>
                                                <th className="py-2.5 px-3 font-semibold">Vehicle</th>
                                                <th className="py-2.5 px-3 font-semibold">Basis</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Base Pay</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Usage Share</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Gross Pay</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Deductions</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">WHT ({params.wht_rate ?? 10}%)</th>
                                                <th className="py-2.5 px-3 font-semibold text-right">Net Payable</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {data.rows.map(row => (
                                                <tr key={row.owner_agreement_id} className="hover:bg-slate-50/80">
                                                    <td className="py-2.5 px-3 font-bold text-blue-600">
                                                        <Link to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.agreement_reference)}`} className="hover:underline">
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
                                                        {(Number(row.mileage_payable) + Number(row.overtime_payable)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2.5 px-3 text-right font-mono font-semibold text-slate-900">
                                                        {Number(row.gross_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2.5 px-3 text-right font-mono text-rose-600">
                                                        {Number(row.deductions).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2.5 px-3 text-right font-mono font-medium text-rose-700">
                                                        {Number(row.wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-2.5 px-3 text-right font-mono font-bold text-emerald-800">
                                                        {Number(row.net_payable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.rows.length === 0 && (
                                                <tr>
                                                    <td colSpan={10} className="py-8 text-center text-slate-400">
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

                    {/* View 2: Statement of Accounts - Vehicle Wise (Detail I) */}
                    {activeTab === 'statement_detail_1' && statement && (
                        <Panel title={`Statement of Accounts - Vehicle Wise (Detail) &mdash; ${statement.vehicle_number}`}>
                            <div className="space-y-4">
                                {/* Header Details */}
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div className="flex flex-col sm:flex-row justify-between items-start gap-4">
                                        <div>
                                            <div className="text-base font-bold text-slate-900">
                                                {statement.vehicle_number}
                                                <span className="ml-3 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                                    {statement.driver_badge}
                                                </span>
                                            </div>
                                            <div className="text-xs text-slate-600 mt-1">
                                                <span className="font-semibold text-slate-800">Owner:</span> {statement.owner_name} {statement.owner_code && `(${statement.owner_code})`}
                                            </div>
                                            <div className="text-xs text-slate-600">
                                                <span className="font-semibold text-slate-800">Hirer / Client:</span> {statement.client_name}
                                            </div>
                                        </div>
                                        <div className="text-right text-xs">
                                            <div className="font-semibold text-slate-800">Period: {statement.period}</div>
                                            <div className="text-slate-500 mt-0.5">Account Ref: {statement.account_reference}</div>
                                            <div className="text-slate-500">Date: {statement.report_date}</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Statement Table */}
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border border-slate-200">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-100 text-slate-700">
                                                <th className="py-2 px-3 font-semibold" style={{ width: '25%' }}>Date & Reference</th>
                                                <th className="py-2 px-3 font-semibold" style={{ width: '40%' }}>Description</th>
                                                <th className="py-2 px-3 font-semibold text-center" style={{ width: '12%' }}>Unit</th>
                                                <th className="py-2 px-3 font-semibold text-right" style={{ width: '11%' }}>Rate</th>
                                                <th className="py-2 px-3 font-semibold text-right" style={{ width: '12%' }}>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {/* Opening Balance */}
                                            <tr className="bg-slate-50/50 font-semibold">
                                                <td className="py-2.5 px-3 text-slate-900">{statement.balance_bf_label}</td>
                                                <td colSpan={3}></td>
                                                <td className="py-2.5 px-3 text-right font-mono text-slate-900">{statement.balance_bf}</td>
                                            </tr>

                                            {/* Payables */}
                                            <tr className="bg-slate-200 font-bold text-slate-800">
                                                <td colSpan={5} className="py-1.5 px-3 uppercase tracking-wider text-[11px]">
                                                    Rental & Other Payable
                                                </td>
                                            </tr>
                                            {statement.payables.map((p, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="py-2 px-3 font-medium text-slate-700">{p.date_reference}</td>
                                                    <td className="py-2 px-3 text-slate-800">{p.description}</td>
                                                    <td className="py-2 px-3 text-center text-slate-600">{p.unit}</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-700">{p.rate}</td>
                                                    <td className="py-2 px-3 text-right font-mono font-semibold text-slate-900">{p.amount}</td>
                                                </tr>
                                            ))}
                                            <tr className="bg-slate-50 font-bold border-t border-slate-200">
                                                <td colSpan={4} className="py-2 px-3 text-right text-slate-700">Total Rental & Other Payable:</td>
                                                <td className="py-2 px-3 text-right font-mono text-blue-900">{statement.total_payable}</td>
                                            </tr>

                                            {/* Settlements */}
                                            <tr className="bg-slate-200 font-bold text-slate-800">
                                                <td colSpan={5} className="py-1.5 px-3 uppercase tracking-wider text-[11px]">
                                                    Settlement
                                                </td>
                                            </tr>
                                            {statement.settlements.map((s, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="py-2 px-3 font-medium text-slate-700">{s.date} &nbsp; {s.reference}</td>
                                                    <td colSpan={3} className="py-2 px-3 text-slate-800">{s.description}</td>
                                                    <td className="py-2 px-3 text-right font-mono font-bold text-blue-700">{s.amount}</td>
                                                </tr>
                                            ))}
                                            {statement.settlements.length === 0 && (
                                                <tr>
                                                    <td colSpan={4} className="py-2 px-3 text-slate-400 italic">No bank vouchers or cheque settlements recorded in this period.</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-400">-</td>
                                                </tr>
                                            )}

                                            {/* Deductions for Repair */}
                                            <tr className="bg-slate-200 font-bold text-slate-800">
                                                <td colSpan={5} className="py-1.5 px-3 uppercase tracking-wider text-[11px]">
                                                    Deduction for Repair
                                                </td>
                                            </tr>
                                            {statement.repair_deductions.map((r, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="py-2 px-3 font-medium text-slate-700">{r.date} &nbsp; {r.reference}</td>
                                                    <td colSpan={3} className="py-2 px-3 text-slate-800">{r.description}</td>
                                                    <td className="py-2 px-3 text-right font-mono font-bold text-rose-700">{r.amount}</td>
                                                </tr>
                                            ))}
                                            {statement.repair_deductions.length === 0 && (
                                                <tr>
                                                    <td colSpan={4} className="py-2 px-3 text-slate-400 italic">No repair chargebacks or debit notes recorded in this period.</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-400">-</td>
                                                </tr>
                                            )}
                                        </tbody>
                                        <tfoot>
                                            <tr className="border-t-2 border-slate-400 bg-slate-100 font-bold">
                                                <td colSpan={4} className="py-3 px-3 text-sm text-slate-900 font-extrabold">{statement.balance_closing_label}</td>
                                                <td className="py-3 px-3 text-right font-mono text-sm font-extrabold text-slate-950">{statement.balance_closing}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </Panel>
                    )}

                    {/* View 3: Statement of Accounts - Vehicle Wise (Detail II) */}
                    {activeTab === 'statement_detail_2' && statement && (
                        <Panel title={`Statement of Accounts - Vehicle Wise (Detail II) &mdash; ${statement.vehicle_number}`}>
                            <div className="space-y-4">
                                {/* Header Details */}
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div className="flex flex-col sm:flex-row justify-between items-start gap-4">
                                        <div>
                                            <div className="text-base font-bold text-slate-900">
                                                {statement.vehicle_number}
                                                <span className="ml-3 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                                    {statement.driver_badge}
                                                </span>
                                            </div>
                                            <div className="text-xs text-slate-600 mt-1">
                                                <span className="font-semibold text-slate-800">Owner:</span> {statement.owner_name} {statement.owner_code && `(${statement.owner_code})`}
                                            </div>
                                            <div className="text-xs text-slate-600">
                                                <span className="font-semibold text-slate-800">Lessor:</span> {statement.owner_name}
                                            </div>
                                        </div>
                                        <div className="text-right text-xs">
                                            <div className="font-semibold text-slate-800">Period: {statement.period}</div>
                                            <div className="text-slate-500 mt-0.5">Account Ref: Detail II</div>
                                            <div className="text-slate-500">Date: {statement.report_date}</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Balance B/F */}
                                <div className="flex justify-between items-center bg-slate-100 border border-slate-300 p-2.5 rounded font-bold text-xs">
                                    <span>{statement.balance_bf_label}</span>
                                    <span className="font-mono text-slate-900">{statement.balance_bf}</span>
                                </div>

                                {/* Operational Matrix */}
                                <div className="font-bold text-xs uppercase tracking-wider text-slate-700">Rental & Other Payable (Operational Matrix)</div>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border border-slate-200">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-100 text-slate-700 text-[10px]">
                                                <th className="py-2 px-2 font-semibold">Client</th>
                                                <th className="py-2 px-2 font-semibold text-center">Start</th>
                                                <th className="py-2 px-2 font-semibold text-center">Finish</th>
                                                <th className="py-2 px-2 font-semibold text-center">Unit</th>
                                                <th className="py-2 px-2 font-semibold text-center">Calc By</th>
                                                <th className="py-2 px-2 font-semibold text-right">KM</th>
                                                <th className="py-2 px-2 font-semibold text-right">Rate</th>
                                                <th className="py-2 px-2 font-semibold text-right">Hire Amt</th>
                                                <th className="py-2 px-2 font-semibold text-center">N/O</th>
                                                <th className="py-2 px-2 font-semibold text-center">D/O</th>
                                                <th className="py-2 px-2 font-semibold text-center">T/O</th>
                                                <th className="py-2 px-2 font-semibold text-right">OT Amt</th>
                                                <th className="py-2 px-2 font-semibold text-right">Salary</th>
                                                <th className="py-2 px-2 font-semibold text-right">BATA</th>
                                                <th className="py-2 px-2 font-semibold text-right">Other</th>
                                                <th className="py-2 px-2 font-semibold text-right">VAT</th>
                                                <th className="py-2 px-2 font-semibold text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 font-mono text-[11px]">
                                            <tr className="hover:bg-slate-50">
                                                <td className="py-2 px-2 font-sans font-bold text-slate-900">{statement.detail_ii_row.client_name}</td>
                                                <td className="py-2 px-2 text-center text-slate-600">{statement.detail_ii_row.hire_start}</td>
                                                <td className="py-2 px-2 text-center text-slate-600">{statement.detail_ii_row.hire_finish}</td>
                                                <td className="py-2 px-2 text-center font-sans text-slate-600">{statement.detail_ii_row.unit}</td>
                                                <td className="py-2 px-2 text-center font-sans text-slate-600">{statement.detail_ii_row.calculated_by}</td>
                                                <td className="py-2 px-2 text-right text-slate-700">{statement.detail_ii_row.total_km}</td>
                                                <td className="py-2 px-2 text-right text-slate-700">{statement.detail_ii_row.non_ac_rate}</td>
                                                <td className="py-2 px-2 text-right font-bold text-slate-900">{statement.detail_ii_row.hire_amount}</td>
                                                <td className="py-2 px-2 text-center text-slate-700">{statement.detail_ii_row.no_hrs}</td>
                                                <td className="py-2 px-2 text-center text-slate-700">{statement.detail_ii_row.do_hrs}</td>
                                                <td className="py-2 px-2 text-center text-slate-700">{statement.detail_ii_row.to_hrs}</td>
                                                <td className="py-2 px-2 text-right font-bold text-blue-800">{statement.detail_ii_row.ot_amount}</td>
                                                <td className="py-2 px-2 text-right text-slate-500">{statement.detail_ii_row.driver_salary}</td>
                                                <td className="py-2 px-2 text-right text-slate-500">{statement.detail_ii_row.driver_bata}</td>
                                                <td className="py-2 px-2 text-right text-slate-700">{statement.detail_ii_row.other_charges}</td>
                                                <td className="py-2 px-2 text-right text-slate-500">{statement.detail_ii_row.vat_amount}</td>
                                                <td className="py-2 px-2 text-right font-bold text-slate-950 bg-slate-50">{statement.detail_ii_row.total_payable}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {/* Settlements and Deductions Table */}
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border border-slate-200">
                                        <tbody>
                                            <tr className="bg-slate-200 font-bold text-slate-800">
                                                <td colSpan={4} className="py-1.5 px-3 uppercase tracking-wider text-[11px]">
                                                    Settlement
                                                </td>
                                            </tr>
                                            {statement.settlements.map((s, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="py-2 px-3 font-medium text-slate-700" style={{ width: '25%' }}>{s.date} &nbsp; {s.reference}</td>
                                                    <td colSpan={2} className="py-2 px-3 text-slate-800">{s.description}</td>
                                                    <td className="py-2 px-3 text-right font-mono font-bold text-blue-700" style={{ width: '15%' }}>{s.amount}</td>
                                                </tr>
                                            ))}
                                            {statement.settlements.length === 0 && (
                                                <tr>
                                                    <td colSpan={3} className="py-2 px-3 text-slate-400 italic">No settlements recorded in this period.</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-400">-</td>
                                                </tr>
                                            )}

                                            <tr className="bg-slate-200 font-bold text-slate-800">
                                                <td colSpan={4} className="py-1.5 px-3 uppercase tracking-wider text-[11px]">
                                                    Deduction for Repair
                                                </td>
                                            </tr>
                                            {statement.repair_deductions.map((r, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="py-2 px-3 font-medium text-slate-700" style={{ width: '25%' }}>{r.date} &nbsp; {r.reference}</td>
                                                    <td colSpan={2} className="py-2 px-3 text-slate-800">{r.description}</td>
                                                    <td className="py-2 px-3 text-right font-mono font-bold text-rose-700" style={{ width: '15%' }}>{r.amount}</td>
                                                </tr>
                                            ))}
                                            {statement.repair_deductions.length === 0 && (
                                                <tr>
                                                    <td colSpan={3} className="py-2 px-3 text-slate-400 italic">No repair deductions recorded in this period.</td>
                                                    <td className="py-2 px-3 text-right font-mono text-slate-400">-</td>
                                                </tr>
                                            )}
                                        </tbody>
                                        <tfoot>
                                            <tr className="border-t-2 border-slate-400 bg-slate-100 font-bold">
                                                <td colSpan={3} className="py-3 px-3 text-sm text-slate-900 font-extrabold">{statement.balance_closing_label}</td>
                                                <td className="py-3 px-3 text-right font-mono text-sm font-extrabold text-slate-950">{statement.balance_closing}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </Panel>
                    )}

                    {/* View 4: WHT Ledger Statement (pmglled.frx) */}
                    {activeTab === 'wht_ledger' && (
                        <Panel title="Ledger Account: Account Code :3305-000 WHT">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '12%' }}>Date</th>
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '15%' }}>Reference</th>
                                            <th className="py-2.5 px-3 font-semibold" style={{ width: '45%' }}>Description</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '9%' }}>Debit</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '10%' }}>Credit</th>
                                            <th className="py-2.5 px-3 font-semibold text-right" style={{ width: '9%' }}>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 font-mono">
                                        {(data.wht_ledger ?? []).map(entry => (
                                            <tr key={entry.reference} className="hover:bg-slate-50/80">
                                                <td className="py-2.5 px-3 text-slate-700">{entry.date}</td>
                                                <td className="py-2.5 px-3 font-bold text-blue-700">{entry.reference}</td>
                                                <td className="py-2.5 px-3 text-slate-800 font-sans font-medium">{entry.description}</td>
                                                <td className="py-2.5 px-3 text-right text-slate-500">{entry.debit || '-'}</td>
                                                <td className="py-2.5 px-3 text-right font-bold text-rose-700">{entry.credit}</td>
                                                <td className="py-2.5 px-3 text-right font-bold text-slate-900">{entry.balance}</td>
                                            </tr>
                                        ))}
                                        {(!data.wht_ledger || data.wht_ledger.length === 0) && (
                                            <tr>
                                                <td colSpan={6} className="py-8 text-center text-slate-400 font-sans">
                                                    No Withholding Tax ledger entries recorded for this period.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                    {data.wht_ledger && data.wht_ledger.length > 0 && (
                                        <tfoot>
                                            <tr className="border-t-2 border-slate-300 bg-slate-50 font-mono font-bold">
                                                <td colSpan={4} className="py-2.5 px-3 text-right font-sans">Total WHT Deducted:</td>
                                                <td className="py-2.5 px-3 text-right text-rose-700">-{Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td className="py-2.5 px-3 text-right text-slate-900">-{Number(data.summary.total_wht_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            </tr>
                                        </tfoot>
                                    )}
                                </table>
                            </div>
                        </Panel>
                    )}
                </>
            )}

            {showSettlementModal && statement && (
                <OwnerSettlementModal
                    open={showSettlementModal}
                    onClose={() => setShowSettlementModal(false)}
                    ownerAgreementId={statement.owner_agreement_id}
                    ownerAgreementReference={statement.agreement_reference}
                    defaultStartDate={params.start_date}
                    defaultEndDate={params.end_date}
                    onBillGenerated={() => {
                        executeFetch({ ...params, report_type: activeTab });
                    }}
                />
            )}
        </div>
    );
}
