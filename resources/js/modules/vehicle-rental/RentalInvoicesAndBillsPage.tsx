import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { AgreementKind, agreementPermissions } from './agreements';
import { BILLING_PERMISSION } from './baseRentBillingApi';
import {
    listRentalBillingDocuments,
    getRentalBillingSummary,
    type RentalBillingDocument,
    type RentalBillingSummary,
    type PaginatedBillingDocuments,
} from './rentalBillingApi';
import { RentalBillingDocumentDetailModal } from './RentalBillingDocumentDetailModal';
import { RentalInvoiceCreateModal } from './RentalInvoiceCreateModal';
import { OwnerSettlementModal } from './OwnerSettlementModal';
import { RentalQuotationPreviewModal } from './RentalQuotationPreviewModal';
import { RentalOwnerBillPreviewModal } from './RentalOwnerBillPreviewModal';

const STATUS_OPTIONS = [
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'posted', label: 'Posted' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Fully Paid' },
    { value: 'cancelled', label: 'Cancelled' },
];

const BALANCE_STATUS_OPTIONS = [
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Fully Paid' },
    { value: 'overdue', label: 'Overdue' },
];

export default function RentalInvoicesAndBillsPage() {
    const auth = useAuth();
    const canCustomerView = hasPermission(auth, agreementPermissions.customer.view);
    const canOwnerView = hasPermission(auth, agreementPermissions.owner.view);
    const canCustomerBill = hasPermission(auth, BILLING_PERMISSION[AgreementKind.Customer]);
    const canOwnerBill = hasPermission(auth, BILLING_PERMISSION[AgreementKind.Owner]);

    const [tab, setTab] = useState<'ar' | 'ap'>(() => (!canCustomerView && canOwnerView ? 'ap' : 'ar'));

    // Keep tab in sync with permissions if changed
    useEffect(() => {
        if (!canCustomerView && canOwnerView && tab === 'ar') {
            setTab('ap');
        } else if (canCustomerView && !canOwnerView && tab === 'ap') {
            setTab('ar');
        }
    }, [canCustomerView, canOwnerView, tab]);

    // Summary metrics
    const [summary, setSummary] = useState<RentalBillingSummary | null>(null);
    const [loadingSummary, setLoadingSummary] = useState(false);

    // List and pagination
    const [paginated, setPaginated] = useState<PaginatedBillingDocuments>({
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
    });
    const [loadingList, setLoadingList] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    // Filters
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [balanceFilter, setBalanceFilter] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);

    // Modals
    const [selectedDetailId, setSelectedDetailId] = useState<number | null>(null);
    const [invoiceCreateOpen, setInvoiceCreateOpen] = useState(false);
    const [invoiceCreateMode, setInvoiceCreateMode] = useState<'cycle' | 'base'>('cycle');
    const [ownerSettlementOpen, setOwnerSettlementOpen] = useState(false);
    const [quotationPreviewOpen, setQuotationPreviewOpen] = useState(false);
    const [ownerBillPreviewOpen, setOwnerBillPreviewOpen] = useState(false);

    const loadSummary = useCallback(async (signal?: AbortSignal) => {
        setLoadingSummary(true);
        try {
            const data = await getRentalBillingSummary(signal);
            setSummary(data);
        } catch {
            // Non-critical, ignore summary error
        } finally {
            setLoadingSummary(false);
        }
    }, []);

    const loadDocuments = useCallback(async (signal?: AbortSignal) => {
        setLoadingList(true);
        setError(null);
        try {
            const result = await listRentalBillingDocuments(
                {
                    direction: tab === 'ar' ? 'outbound' : 'inbound',
                    search: search.trim() || undefined,
                    status: statusFilter || undefined,
                    balance_status: balanceFilter || undefined,
                    date_from: dateFrom || undefined,
                    date_to: dateTo || undefined,
                    page,
                    per_page: 25,
                },
                signal
            );
            setPaginated(result);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoadingList(false);
        }
    }, [tab, search, statusFilter, balanceFilter, dateFrom, dateTo, page]);

    useEffect(() => {
        const controller = new AbortController();
        loadSummary(controller.signal);
        return () => controller.abort();
    }, [loadSummary]);

    useEffect(() => {
        const controller = new AbortController();
        loadDocuments(controller.signal);
        return () => controller.abort();
    }, [loadDocuments]);

    function handleTabSwitch(nextTab: 'ar' | 'ap') {
        if (nextTab === tab) return;
        setTab(nextTab);
        setPage(1);
    }

    function handleRefreshAll() {
        loadSummary();
        loadDocuments();
    }

    return (
        <div className="space-y-6 p-6">
            {/* Top Workspace Header */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                        Invoices & Vehicle Owner Bills
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Centralized financial billing cockpit for Customer Invoices (AR) and Vehicle Owner Payables (AP).
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleRefreshAll}
                        title="Refresh register data"
                    >
                        Refresh
                    </Button>

                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => {
                            if (tab === 'ar') {
                                setQuotationPreviewOpen(true);
                            } else {
                                setOwnerBillPreviewOpen(true);
                            }
                        }}
                        title={tab === 'ar' ? 'Preview Quotation Document' : 'Preview Pro-Forma Settlement'}
                    >
                        <span>📄</span>
                        <span className="ml-1">{tab === 'ar' ? 'Quotation Preview' : 'Settlement Preview'}</span>
                    </Button>

                    {canCustomerBill && (
                        <>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setInvoiceCreateMode('base');
                                    setInvoiceCreateOpen(true);
                                }}
                            >
                                + Advance Base Rent
                            </Button>

                            <Button
                                type="button"
                                variant="primary"
                                onClick={() => {
                                    setInvoiceCreateMode('cycle');
                                    setInvoiceCreateOpen(true);
                                }}
                            >
                                + Billing Cycle Invoice
                            </Button>
                        </>
                    )}

                    {canOwnerBill && (
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setOwnerSettlementOpen(true)}
                        >
                            + Owner Settlement Bill
                        </Button>
                    )}
                </div>
            </div>

            {/* Error Notification */}
            <ErrorAlert error={error} inline />

            {/* Direct Page Switcher */}
            <div className="flex items-center gap-4 text-xs font-semibold text-gray-500 border-b border-gray-100 dark:border-gray-800 pb-2">
                <span className="text-gray-400">Dedicated Views:</span>
                <a
                    href="/vehicle-rental/customer-invoices"
                    className="text-blue-600 dark:text-blue-400 hover:underline font-bold"
                >
                    Customer Invoices Page (AR) ↗
                </a>
                <span className="text-gray-300">|</span>
                <a
                    href="/vehicle-rental/owner-bills"
                    className="text-purple-600 dark:text-purple-400 hover:underline font-bold"
                >
                    Vehicle Owner Bills Page (AP) ↗
                </a>
            </div>

            {/* Navigation Tabs (AR vs AP) */}
            <div className="border-b border-slate-200">
                <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                    {canCustomerView && (
                        <button
                            type="button"
                            onClick={() => handleTabSwitch('ar')}
                            className={`whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition-all ${
                                tab === 'ar'
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                            }`}
                        >
                            Customer Invoices (AR)
                            {summary?.customer_ar && (
                                <span className="ml-2.5 py-0.5 px-2 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    {summary.customer_ar.overdue_count > 0 ? `${summary.customer_ar.overdue_count} Overdue` : 'Active'}
                                </span>
                            )}
                        </button>
                    )}

                    {canOwnerView && (
                        <button
                            type="button"
                            onClick={() => handleTabSwitch('ap')}
                            className={`whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition-all ${
                                tab === 'ap'
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                            }`}
                        >
                            Vehicle Owner Bills (AP)
                            {summary?.owner_ap && (
                                <span className="ml-2.5 py-0.5 px-2 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    {summary.owner_ap.overdue_count > 0 ? `${summary.owner_ap.overdue_count} Overdue` : 'Active'}
                                </span>
                            )}
                        </button>
                    )}
                </nav>
            </div>

            {/* Metric KPI Cards */}
            {tab === 'ar' && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Total Invoiced (AR)
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-slate-900">
                            {summary ? <MoneyDisplay value={summary.customer_ar.total_invoiced} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Total gross charges billed</div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Total Collected
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-emerald-600">
                            {summary ? <MoneyDisplay value={summary.customer_ar.total_collected} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Paid customer receipts</div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Outstanding Receivables
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-blue-600">
                            {summary ? <MoneyDisplay value={summary.customer_ar.total_outstanding} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Current open receivables</div>
                    </div>

                    <div className="p-4 rounded-xl border border-rose-200 bg-rose-50/50 shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-rose-700">
                            Overdue Receivables
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-rose-600">
                            {summary ? <MoneyDisplay value={summary.customer_ar.overdue_amount} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-rose-800">
                            {summary ? `${summary.customer_ar.overdue_count} invoices overdue` : '-'}
                        </div>
                    </div>
                </div>
            )}

            {tab === 'ap' && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Gross Rent Billed (AP)
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-slate-900">
                            {summary ? <MoneyDisplay value={summary.owner_ap.total_gross_billed} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Gross lessor lease hire</div>
                    </div>

                    <div className="p-4 rounded-xl border border-amber-200 bg-amber-50/50 shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-amber-800">
                            10% WHT Withheld
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-amber-700">
                            {summary ? <MoneyDisplay value={summary.owner_ap.total_wht_withheld} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-amber-800">Statutory tax retained</div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Net Paid to Owners
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-emerald-600">
                            {summary ? <MoneyDisplay value={summary.owner_ap.total_net_paid} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Total owner disbursements</div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Outstanding Payables
                        </div>
                        <div className="mt-2 text-xl font-bold font-mono text-blue-600">
                            {summary ? <MoneyDisplay value={summary.owner_ap.total_outstanding} /> : '-'}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">Net payable balance due</div>
                    </div>
                </div>
            )}

            {/* Filter Bar */}
            <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-4">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <Input
                        label="Search"
                        placeholder="Invoice #, party name, agreement ref..."
                        value={search}
                        onChange={e => {
                            setSearch(e.target.value);
                            setPage(1);
                        }}
                    />

                    <Select
                        label="Document Status"
                        options={STATUS_OPTIONS}
                        value={statusFilter}
                        onChange={e => {
                            setStatusFilter(e.target.value);
                            setPage(1);
                        }}
                        placeholder="All Statuses"
                    />

                    <Select
                        label="Balance Status"
                        options={BALANCE_STATUS_OPTIONS}
                        value={balanceFilter}
                        onChange={e => {
                            setBalanceFilter(e.target.value);
                            setPage(1);
                        }}
                        placeholder="All Balances"
                    />

                    <Input
                        label="From Date"
                        type="date"
                        value={dateFrom}
                        onChange={e => {
                            setDateFrom(e.target.value);
                            setPage(1);
                        }}
                    />

                    <Input
                        label="To Date"
                        type="date"
                        value={dateTo}
                        onChange={e => {
                            setDateTo(e.target.value);
                            setPage(1);
                        }}
                    />
                </div>
            </div>

            {/* Documents Table */}
            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                {loadingList && <LoadingState label="Loading billing documents..." />}

                {!loadingList && (
                    <table className="min-w-full text-xs text-left divide-y divide-slate-200">
                        <thead className="bg-slate-50 text-slate-600 font-semibold">
                            <tr>
                                <th className="py-3 px-4">Document #</th>
                                <th className="py-3 px-4">Date</th>
                                <th className="py-3 px-4">Due Date</th>
                                <th className="py-3 px-4">{tab === 'ar' ? 'Customer' : 'Vehicle Owner'}</th>
                                <th className="py-3 px-4">Agreement Ref</th>
                                <th className="py-3 px-4">Vehicle</th>
                                {tab === 'ap' && <th className="py-3 px-4 text-right">Gross Rent</th>}
                                {tab === 'ap' && <th className="py-3 px-4 text-right">WHT (10%)</th>}
                                <th className="py-3 px-4 text-right">{tab === 'ar' ? 'Grand Total' : 'Net Payable'}</th>
                                <th className="py-3 px-4 text-right">Paid</th>
                                <th className="py-3 px-4 text-right">Balance Due</th>
                                <th className="py-3 px-4 text-center">Status</th>
                                <th className="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-slate-800">
                            {paginated.data.map(doc => (
                                <tr
                                    key={doc.id}
                                    onClick={() => setSelectedDetailId(doc.id)}
                                    className="cursor-pointer hover:bg-slate-50/75 transition-colors"
                                >
                                    <td className="py-3 px-4 font-mono font-bold text-slate-900">
                                        {doc.invoice_number}
                                    </td>
                                    <td className="py-3 px-4 text-slate-600">
                                        {doc.invoice_date ?? '-'}
                                    </td>
                                    <td className="py-3 px-4 text-slate-600">
                                        {doc.due_date ?? '-'}
                                    </td>
                                    <td className="py-3 px-4">
                                        <div className="font-medium text-slate-900">{doc.party.name}</div>
                                        <div className="text-2xs text-slate-400">{doc.party.code}</div>
                                    </td>
                                    <td className="py-3 px-4 font-medium text-slate-700">
                                        {doc.agreement?.reference ?? '-'}
                                    </td>
                                    <td className="py-3 px-4">
                                        {doc.vehicle?.registration_number ? (
                                            <div>
                                                <div className="font-semibold text-slate-900">
                                                    {doc.vehicle.registration_number}
                                                </div>
                                                <div className="text-2xs text-slate-400">
                                                    {doc.vehicle.make_model}
                                                </div>
                                            </div>
                                        ) : (
                                            <span className="text-slate-400">-</span>
                                        )}
                                    </td>
                                    {tab === 'ap' && (
                                        <td className="py-3 px-4 text-right font-mono text-slate-700">
                                            <MoneyDisplay value={doc.gross_amount} />
                                        </td>
                                    )}
                                    {tab === 'ap' && (
                                        <td className="py-3 px-4 text-right font-mono text-amber-700">
                                            {Number(doc.wht_amount) > 0 ? (
                                                <>- <MoneyDisplay value={doc.wht_amount} /></>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                    )}
                                    <td className="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                        <MoneyDisplay value={doc.grand_total} />
                                    </td>
                                    <td className="py-3 px-4 text-right font-mono font-medium text-emerald-600">
                                        <MoneyDisplay value={doc.paid_total} />
                                    </td>
                                    <td className="py-3 px-4 text-right font-mono font-bold">
                                        <span className={Number(doc.balance_due) > 0 ? 'text-rose-600' : 'text-slate-400'}>
                                            <MoneyDisplay value={doc.balance_due} />
                                        </span>
                                    </td>
                                    <td className="py-3 px-4 text-center">
                                        <div className="flex flex-col items-center gap-1">
                                            <StatusBadge status={doc.status} />
                                            {doc.balance_status === 'overdue' && (
                                                <span className="text-2xs font-bold text-rose-700">Overdue</span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="py-3 px-4 text-right" onClick={e => e.stopPropagation()}>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => setSelectedDetailId(doc.id)}
                                        >
                                            View
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {paginated.data.length === 0 && (
                                <tr>
                                    <td colSpan={tab === 'ap' ? 13 : 11} className="py-8 text-center text-slate-400">
                                        No billing documents found matching the selected filters.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                )}

                {/* Pagination */}
                <div className="p-4 border-t border-slate-200">
                    <Pagination
                        meta={paginated.meta}
                        onPageChange={nextPage => setPage(nextPage)}
                        disabled={loadingList}
                    />
                </div>
            </div>

            {/* Document Detail Modal */}
            <RentalBillingDocumentDetailModal
                open={Boolean(selectedDetailId)}
                documentId={selectedDetailId}
                onClose={() => setSelectedDetailId(null)}
                onDocumentUpdated={handleRefreshAll}
            />

            {/* Invoice Creation Wizard Modal */}
            <RentalInvoiceCreateModal
                open={invoiceCreateOpen}
                onClose={() => setInvoiceCreateOpen(false)}
                initialMode={invoiceCreateMode}
                onInvoiceCreated={handleRefreshAll}
            />

            {/* Owner Settlement Modal */}
            <OwnerSettlementModal
                open={ownerSettlementOpen}
                onClose={() => setOwnerSettlementOpen(false)}
                onBillGenerated={() => {
                    handleRefreshAll();
                }}
            />

            {/* Quotation Preview Modal */}
            <RentalQuotationPreviewModal
                open={quotationPreviewOpen}
                onClose={() => setQuotationPreviewOpen(false)}
                onProceedToCreateInvoice={() => {
                    setInvoiceCreateMode('cycle');
                    setInvoiceCreateOpen(true);
                }}
            />

            {/* Owner Bill Preview Modal */}
            <RentalOwnerBillPreviewModal
                open={ownerBillPreviewOpen}
                onClose={() => setOwnerBillPreviewOpen(false)}
                onProceedToGenerateBill={() => {
                    setOwnerSettlementOpen(true);
                }}
            />
        </div>
    );
}
