/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState, type FormEvent } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue, formatBusinessDateTime, shiftBusinessDate } from '@/shared/utils/businessDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { ExportActions } from '../components/ExportActions';
import { loadCashiersList, runCashierReport } from '../reportingApi';
import type {
    CashierLookupUser,
    CashierReportDirection,
    CashierReportParams,
    CashierReportResult,
    CashierReportStatus,
} from '../cashierReportTypes';

const DIRECTION_OPTIONS = [
    { value: 'all', label: 'All Activities (Inbound & Outbound)' },
    { value: 'inbound', label: 'Collections Only (Inbound Receipts)' },
    { value: 'outbound', label: 'Disbursements & Refunds Only (Outbound)' },
] as const;

const METHOD_OPTIONS = [
    { value: 'all', label: 'All Payment Methods' },
    { value: 'cash', label: 'Cash Only' },
    { value: 'card', label: 'Card Only' },
    { value: 'bank_transfer', label: 'Bank Transfer Only' },
    { value: 'cheque', label: 'Cheque Only' },
] as const;

const STATUS_OPTIONS = [
    { value: 'approved', label: 'Approved Documents (Standard)' },
    { value: 'all', label: 'All Document Statuses' },
    { value: 'draft', label: 'Drafts Only' },
    { value: 'voided', label: 'Voided Only' },
] as const;

type ActiveTab = 'transactions' | 'methods' | 'cashiers';

function defaultPeriod(): CashierReportParams {
    const today = businessDateInputValue();
    return {
        date_from: today,
        date_to: today,
        cashier_user_id: null,
        payment_method: 'all',
        direction: 'all',
        document_status: 'approved',
        search: '',
        page: 1,
        per_page: 25,
    };
}

export default function CashierReportPage() {
    const [draft, setDraft] = useState<CashierReportParams>(defaultPeriod);
    const [filters, setFilters] = useState<CashierReportParams>(defaultPeriod);
    const [cashiers, setCashiers] = useState<CashierLookupUser[]>([]);
    const [activeTab, setActiveTab] = useState<ActiveTab>('transactions');
    const [loaded, setLoaded] = useState<{
        filters: CashierReportParams;
        result: CashierReportResult | null;
        error: ApiError | null;
    } | null>(null);

    const loading = loaded?.filters !== filters;
    const result = loaded?.filters === filters ? loaded.result : null;
    const error = loaded?.filters === filters ? loaded.error : null;

    useEffect(() => {
        const controller = new AbortController();
        void loadCashiersList(controller.signal)
            .then((list) => {
                if (!controller.signal.aborted) setCashiers(list);
            })
            .catch(() => {});
        return () => controller.abort();
    }, []);

    useEffect(() => {
        const controller = new AbortController();
        void runCashierReport(filters, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setLoaded({ filters, result: data, error: null });
            })
            .catch((reason) => {
                if (!controller.signal.aborted) setLoaded({ filters, result: null, error: toApiError(reason) });
            });
        return () => controller.abort();
    }, [filters]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setFilters({ ...draft, page: 1 });
    };

    const reset = () => {
        const resetParams = defaultPeriod();
        setDraft(resetParams);
        setFilters(resetParams);
    };

    const setDatePreset = (preset: 'today' | 'yesterday' | 'this_week' | 'this_month') => {
        const todayStr = businessDateInputValue();
        let fromStr = todayStr;
        let toStr = todayStr;

        if (preset === 'yesterday') {
            fromStr = shiftBusinessDate(todayStr, -1);
            toStr = fromStr;
        } else if (preset === 'this_week') {
            const [y, m, d] = todayStr.split('-').map(Number);
            const utcDate = new Date(Date.UTC(y, m - 1, d));
            const dayOfWeek = (utcDate.getUTCDay() + 6) % 7;
            fromStr = shiftBusinessDate(todayStr, -dayOfWeek);
            toStr = todayStr;
        } else if (preset === 'this_month') {
            fromStr = `${todayStr.slice(0, 7)}-01`;
            toStr = todayStr;
        }

        setDraft((prev) => ({ ...prev, date_from: fromStr, date_to: toStr }));
        setFilters((prev) => ({ ...prev, date_from: fromStr, date_to: toStr, page: 1 }));
    };

    const handlePageChange = (newPage: number) => {
        setDraft((prev) => ({ ...prev, page: newPage }));
        setFilters((prev) => ({ ...prev, page: newPage }));
    };

    const handlePerPageChange = (newPerPage: number) => {
        setDraft((prev) => ({ ...prev, per_page: newPerPage, page: 1 }));
        setFilters((prev) => ({ ...prev, per_page: newPerPage, page: 1 }));
    };

    const cashierSelectOptions = [
        { value: '', label: 'All Cashiers / Tellers' },
        ...cashiers.map((c) => ({ value: String(c.id), label: `${c.name} (${c.email})` })),
    ];

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Cashier Report"
                description="Cash drawer balancing, front-desk collections, tender distribution, and cashier audit trail."
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <LinkButton to="/reports" variant="secondary">
                            All Reports
                        </LinkButton>
                        <ExportActions reportKey="cashier" params={filters} />
                    </div>
                }
            />

            <ErrorAlert error={error} />

            <Panel title="Report Filters">
                <form className="space-y-4" onSubmit={apply}>
                    <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-3 text-xs font-medium text-slate-600">
                        <span className="text-slate-400">Quick Presets:</span>
                        <button
                            type="button"
                            onClick={() => setDatePreset('today')}
                            className="rounded-md bg-slate-100 px-2.5 py-1 text-slate-700 hover:bg-slate-200"
                        >
                            Today
                        </button>
                        <button
                            type="button"
                            onClick={() => setDatePreset('yesterday')}
                            className="rounded-md bg-slate-100 px-2.5 py-1 text-slate-700 hover:bg-slate-200"
                        >
                            Yesterday
                        </button>
                        <button
                            type="button"
                            onClick={() => setDatePreset('this_week')}
                            className="rounded-md bg-slate-100 px-2.5 py-1 text-slate-700 hover:bg-slate-200"
                        >
                            This Week
                        </button>
                        <button
                            type="button"
                            onClick={() => setDatePreset('this_month')}
                            className="rounded-md bg-slate-100 px-2.5 py-1 text-slate-700 hover:bg-slate-200"
                        >
                            This Month
                        </button>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Input
                            label="From Date"
                            type="date"
                            required
                            value={draft.date_from}
                            max={draft.date_to}
                            onChange={(e) => setDraft((c) => ({ ...c, date_from: e.target.value }))}
                        />
                        <Input
                            label="To Date"
                            type="date"
                            required
                            value={draft.date_to}
                            min={draft.date_from}
                            onChange={(e) => setDraft((c) => ({ ...c, date_to: e.target.value }))}
                        />
                        <Select
                            label="Cashier Operator"
                            value={draft.cashier_user_id ? String(draft.cashier_user_id) : ''}
                            options={cashierSelectOptions}
                            onChange={(e) =>
                                setDraft((c) => ({
                                    ...c,
                                    cashier_user_id: e.target.value ? Number(e.target.value) : null,
                                }))
                            }
                        />
                        <Select
                            label="Payment Method"
                            value={draft.payment_method ?? 'all'}
                            options={[...METHOD_OPTIONS]}
                            onChange={(e) => setDraft((c) => ({ ...c, payment_method: e.target.value }))}
                        />
                        <Select
                            label="Activity Direction"
                            value={draft.direction ?? 'all'}
                            options={[...DIRECTION_OPTIONS]}
                            onChange={(e) =>
                                setDraft((c) => ({
                                    ...c,
                                    direction: e.target.value as CashierReportDirection,
                                }))
                            }
                        />
                        <Select
                            label="Document Status"
                            value={draft.document_status ?? 'approved'}
                            options={[...STATUS_OPTIONS]}
                            onChange={(e) =>
                                setDraft((c) => ({
                                    ...c,
                                    document_status: e.target.value as CashierReportStatus,
                                }))
                            }
                        />
                        <div className="sm:col-span-2">
                            <Input
                                label="Search"
                                placeholder="Search by receipt #, customer name, or invoice #..."
                                value={draft.search ?? ''}
                                onChange={(e) => setDraft((c) => ({ ...c, search: e.target.value }))}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-2">
                        <Button type="button" variant="secondary" onClick={reset}>
                            Reset
                        </Button>
                        <Button type="submit" loading={loading}>
                            Apply Filters
                        </Button>
                    </div>
                </form>
            </Panel>

            {loading && !result ? <LoadingState label="Loading cashier report metrics and transactions..." /> : null}

            {result && (
                <div className="space-y-6">
                    {/* Header Details */}
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <div>
                            <span className="font-semibold text-slate-900">{result.organization_unit}</span>
                            <span className="mx-2 text-slate-300">•</span>
                            <span>
                                Reporting Period: {result.period.date_from} to {result.period.date_to}
                            </span>
                        </div>
                        <div className="text-xs text-slate-500">Currency: {result.currency}</div>
                    </div>

                    {/* KPI Metric Cards */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        {/* 1. Net Cash in Drawer (Featured Card) */}
                        <div className="rounded-xl border border-emerald-300 bg-linear-to-br from-emerald-50 to-emerald-100/70 p-4 shadow-xs">
                            <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-emerald-800">
                                <span>💵 Net Cash In Drawer</span>
                            </div>
                            <div className="mt-2 text-2xl font-bold text-emerald-950">
                                {formatMoney(result.summary.net_cash_in_drawer, result.currency)}
                            </div>
                            <div className="mt-1 flex items-center justify-between text-xs text-emerald-700">
                                <span>In: {formatMoney(result.summary.cash_inflow, result.currency)}</span>
                                <span>Out: {formatMoney(result.summary.cash_outflow, result.currency)}</span>
                            </div>
                        </div>

                        {/* 2. Total Inflow */}
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Total Inflow (Gross)
                            </div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">
                                {formatMoney(result.summary.total_inflow, result.currency)}
                            </div>
                            <div className="mt-1 text-xs text-slate-500">
                                {result.summary.inbound_count} receipts recorded
                            </div>
                        </div>

                        {/* 3. Total Outflow */}
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Total Outflow (Payouts)
                            </div>
                            <div className="mt-2 text-2xl font-bold text-rose-700">
                                {formatMoney(result.summary.total_outflow, result.currency)}
                            </div>
                            <div className="mt-1 text-xs text-slate-500">
                                {result.summary.outbound_count} refunds/disbursements
                            </div>
                        </div>

                        {/* 4. Net Collections */}
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Net Collections
                            </div>
                            <div className="mt-2 text-2xl font-bold text-blue-900">
                                {formatMoney(result.summary.net_collections, result.currency)}
                            </div>
                            <div className="mt-1 text-xs text-slate-500">Gross Inflow minus Outflow</div>
                        </div>

                        {/* 5. Non-Cash Total */}
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Non-Cash Collections
                            </div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">
                                {formatMoney(result.summary.non_cash_total, result.currency)}
                            </div>
                            <div className="mt-1 text-xs text-slate-500">Card, Bank Transfer, Cheque</div>
                        </div>

                        {/* 6. Total Transactions */}
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Total Activities
                            </div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">
                                {result.summary.total_transactions}
                            </div>
                            <div className="mt-1 text-xs text-slate-500">
                                {result.summary.inbound_count} In / {result.summary.outbound_count} Out
                            </div>
                        </div>
                    </div>

                    {/* Section Switcher Tabs */}
                    <div className="flex border-b border-slate-200">
                        <button
                            type="button"
                            onClick={() => setActiveTab('transactions')}
                            className={`flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition ${
                                activeTab === 'transactions'
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800'
                            }`}
                        >
                            <span>Detailed Transactions</span>
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                {result.transactions.meta.total}
                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('methods')}
                            className={`flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition ${
                                activeTab === 'methods'
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800'
                            }`}
                        >
                            <span>Payment Method Breakdown</span>
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                {result.payment_methods.length}
                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('cashiers')}
                            className={`flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition ${
                                activeTab === 'cashiers'
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800'
                            }`}
                        >
                            <span>Cashier Summary</span>
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                {result.cashiers.length}
                            </span>
                        </button>
                    </div>

                    {/* TAB 1: Detailed Transactions Table */}
                    {activeTab === 'transactions' && (
                        <div className="space-y-4">
                            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        <tr>
                                            <th className="px-4 py-3">Time & Date</th>
                                            <th className="px-4 py-3">Receipt / Payment #</th>
                                            <th className="px-4 py-3">Type</th>
                                            <th className="px-4 py-3">Cashier</th>
                                            <th className="px-4 py-3">Customer / Payee</th>
                                            <th className="px-4 py-3">Tenders & Allocations</th>
                                            <th className="px-4 py-3 text-right">Amount</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {result.transactions.data.length === 0 ? (
                                            <tr>
                                                <td colSpan={8} className="px-4 py-8 text-center text-slate-500">
                                                    No cashier transactions found matching the selected filters.
                                                </td>
                                            </tr>
                                        ) : (
                                            result.transactions.data.map((row) => {
                                                const isInbound = row.direction === 'inbound';
                                                return (
                                                    <tr key={row.id} className="hover:bg-slate-50/80 transition-colors">
                                                        <td className="whitespace-nowrap px-4 py-3">
                                                            <div className="font-medium text-slate-900">
                                                                {row.payment_date}
                                                            </div>
                                                            <div className="text-xs text-slate-400">
                                                                {row.created_at ? formatBusinessDateTime(row.created_at) : '-'}
                                                            </div>
                                                        </td>
                                                        <td className="whitespace-nowrap px-4 py-3 font-mono font-medium text-blue-600">
                                                            {row.payment_number}
                                                        </td>
                                                        <td className="whitespace-nowrap px-4 py-3">
                                                            <span
                                                                className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                    isInbound
                                                                        ? 'bg-emerald-50 text-emerald-700'
                                                                        : 'bg-rose-50 text-rose-700'
                                                                }`}
                                                            >
                                                                {row.direction_label}
                                                            </span>
                                                        </td>
                                                        <td className="whitespace-nowrap px-4 py-3">
                                                            <span className="font-medium text-slate-800">
                                                                {row.cashier_name}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <div className="font-medium text-slate-900">
                                                                {row.party_name}
                                                            </div>
                                                            {row.reference_number && (
                                                                <div className="text-xs text-slate-400">
                                                                    Ref: {row.reference_number}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {row.lines.map((l, idx) => (
                                                                    <span
                                                                        key={idx}
                                                                        className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-700"
                                                                    >
                                                                        <span className="font-semibold">{l.method_name}:</span>&nbsp;{formatMoney(l.amount, row.currency_code)}
                                                                        {l.instrument_number ? ` (#${l.instrument_number})` : ''}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                            {row.allocations.length > 0 && (
                                                                <div className="mt-1 flex flex-wrap gap-1 text-xs text-slate-500">
                                                                    <span className="text-slate-400">Settled:</span>
                                                                    {row.allocations.map((a, idx) => (
                                                                        <span
                                                                            key={idx}
                                                                            className="font-mono text-slate-600"
                                                                        >
                                                                            {a.invoice_number} ({formatMoney(a.allocated_amount, row.currency_code)})
                                                                            {idx < row.allocations.length - 1 ? ',' : ''}
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="whitespace-nowrap px-4 py-3 text-right">
                                                            <span
                                                                className={`font-semibold ${
                                                                    isInbound ? 'text-emerald-700' : 'text-rose-700'
                                                                }`}
                                                            >
                                                                {isInbound ? '+' : '-'}
                                                                {formatMoney(row.total_amount, row.currency_code)}
                                                            </span>
                                                        </td>
                                                        <td className="whitespace-nowrap px-4 py-3 text-center">
                                                            <StatusBadge
                                                                status={row.document_status}
                                                                label={row.document_status}
                                                            />
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination Controls */}
                            {result.transactions.meta.last_page > 1 && (
                                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                                    <div className="text-xs text-slate-500">
                                        Showing {result.transactions.meta.from ?? 0} to {result.transactions.meta.to ?? 0} of {result.transactions.meta.total} transactions
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <select
                                            aria-label="Rows per page"
                                            value={filters.per_page ?? 25}
                                            onChange={(e) => handlePerPageChange(Number(e.target.value))}
                                            className="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs"
                                        >
                                            <option value={10}>10 per page</option>
                                            <option value={25}>25 per page</option>
                                            <option value={50}>50 per page</option>
                                            <option value={100}>100 per page</option>
                                        </select>
                                        <Button
                                            variant="secondary"
                                            disabled={result.transactions.meta.current_page <= 1}
                                            onClick={() => handlePageChange(result.transactions.meta.current_page - 1)}
                                        >
                                            Previous
                                        </Button>
                                        <span className="px-2 text-xs font-semibold text-slate-700">
                                            Page {result.transactions.meta.current_page} of {result.transactions.meta.last_page}
                                        </span>
                                        <Button
                                            variant="secondary"
                                            disabled={result.transactions.meta.current_page >= result.transactions.meta.last_page}
                                            onClick={() => handlePageChange(result.transactions.meta.current_page + 1)}
                                        >
                                            Next
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* TAB 2: Payment Method Breakdown */}
                    {activeTab === 'methods' && (
                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    <tr>
                                        <th className="px-4 py-3">Payment Method</th>
                                        <th className="px-4 py-3">Type</th>
                                        <th className="px-4 py-3 text-right">Inflow (Receipts)</th>
                                        <th className="px-4 py-3 text-center">Inflow Count</th>
                                        <th className="px-4 py-3 text-right">Outflow (Payouts)</th>
                                        <th className="px-4 py-3 text-center">Outflow Count</th>
                                        <th className="px-4 py-3 text-right">Net Collections</th>
                                        <th className="px-4 py-3 text-center">Total Activities</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {result.payment_methods.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-8 text-center text-slate-500">
                                                No payment method data available for the period.
                                            </td>
                                        </tr>
                                    ) : (
                                        result.payment_methods.map((method) => {
                                            const isCash = method.type === 'cash' || method.key.toLowerCase() === 'cash';
                                            return (
                                                <tr
                                                    key={method.key}
                                                    className={`hover:bg-slate-50/80 transition-colors ${
                                                        isCash ? 'bg-emerald-50/30 font-medium' : ''
                                                    }`}
                                                >
                                                    <td className="whitespace-nowrap px-4 py-3">
                                                        <div className="font-semibold text-slate-900 flex items-center gap-1.5">
                                                            {isCash && <span>💵</span>}
                                                            {method.name}
                                                        </div>
                                                        <div className="text-xs text-slate-400 font-mono">
                                                            {method.key}
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-xs uppercase tracking-wider text-slate-500">
                                                        {method.type}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-emerald-700">
                                                        {formatMoney(method.inflow, result.currency)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-center text-xs text-slate-600">
                                                        {method.inflow_count}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-rose-700">
                                                        {formatMoney(method.outflow, result.currency)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-center text-xs text-slate-600">
                                                        {method.outflow_count}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-900">
                                                        {formatMoney(method.net, result.currency)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-center font-semibold text-slate-700">
                                                        {method.total_count}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* TAB 3: Cashier Summary */}
                    {activeTab === 'cashiers' && (
                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    <tr>
                                        <th className="px-4 py-3">Cashier Operator</th>
                                        <th className="px-4 py-3 text-right">Inflow (Receipts)</th>
                                        <th className="px-4 py-3 text-right">Outflow (Payouts)</th>
                                        <th className="px-4 py-3 text-right">Net Total</th>
                                        <th className="px-4 py-3 text-right">Net Drawer Cash</th>
                                        <th className="px-4 py-3 text-right">Non-Cash Net</th>
                                        <th className="px-4 py-3 text-center">Transactions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {result.cashiers.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                                                No cashier operator records found for this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        result.cashiers.map((cashier, idx) => (
                                            <tr key={idx} className="hover:bg-slate-50/80 transition-colors">
                                                <td className="whitespace-nowrap px-4 py-3">
                                                    <div className="font-semibold text-slate-900">
                                                        {cashier.cashier_name}
                                                    </div>
                                                    {cashier.cashier_email && (
                                                        <div className="text-xs text-slate-400">
                                                            {cashier.cashier_email}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-emerald-700">
                                                    {formatMoney(cashier.inflow, result.currency)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-rose-700">
                                                    {formatMoney(cashier.outflow, result.currency)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-900">
                                                    {formatMoney(cashier.net, result.currency)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right font-bold text-emerald-800">
                                                    {formatMoney(cashier.cash_net, result.currency)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-blue-900">
                                                    {formatMoney(cashier.non_cash_net, result.currency)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center font-semibold text-slate-700">
                                                    {cashier.transaction_count}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
