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
import { Modal } from '@/shared/components/Modal';
import { Textarea } from '@/shared/components/Textarea';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { AgreementKind, agreementPermissions } from './agreements';
import { BILLING_PERMISSION } from './baseRentBillingApi';
import {
    listRentalBillingDocuments,
    getRentalBillingSummary,
    updateRentalBillingDocument,
    cancelRentalBillingDocument,
    type RentalBillingDocument,
    type RentalBillingSummary,
    type PaginatedBillingDocuments,
} from './rentalBillingApi';
import { RentalBillingDocumentDetailModal } from './RentalBillingDocumentDetailModal';
import { RentalInvoiceCreateModal } from './RentalInvoiceCreateModal';
import { RentalQuotationPreviewModal } from './RentalQuotationPreviewModal';

const STATUS_OPTIONS = [
    { value: '', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'posted', label: 'Posted' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Fully Paid' },
    { value: 'cancelled', label: 'Cancelled' },
];

const BALANCE_STATUS_OPTIONS = [
    { value: '', label: 'All Balances' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Fully Paid' },
    { value: 'overdue', label: 'Overdue' },
];

export default function CustomerInvoicesPage() {
    const auth = useAuth();
    const canCustomerView = hasPermission(auth, agreementPermissions.customer.view);
    const canCustomerBill = hasPermission(auth, BILLING_PERMISSION[AgreementKind.Customer]);

    // Financial Summary
    const [summary, setSummary] = useState<RentalBillingSummary | null>(null);
    const [loadingSummary, setLoadingSummary] = useState(false);

    // List and Pagination
    const [paginated, setPaginated] = useState<PaginatedBillingDocuments>({
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
    });
    const [loadingList, setLoadingList] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    // Filters
    const [search, setSearch] = useState('');
    const [selectedCustomer, setSelectedCustomer] = useState<NamedResource | null>(null);
    const [statusFilter, setStatusFilter] = useState('');
    const [balanceFilter, setBalanceFilter] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);

    // Modals
    const [selectedDetailId, setSelectedDetailId] = useState<number | null>(null);
    const [invoiceCreateOpen, setInvoiceCreateOpen] = useState(false);
    const [quotationPreviewOpen, setQuotationPreviewOpen] = useState(false);
    const [prefilledAgreementId, setPrefilledAgreementId] = useState<number | undefined>(undefined);

    // Draft Editing
    const [editingDoc, setEditingDoc] = useState<RentalBillingDocument | null>(null);
    const [editDueDate, setEditDueDate] = useState('');
    const [editNotes, setEditNotes] = useState('');
    const [submittingEdit, setSubmittingEdit] = useState(false);
    const [editError, setEditError] = useState<ApiError | null>(null);

    // Draft Cancellation
    const [cancellingDoc, setCancellingDoc] = useState<RentalBillingDocument | null>(null);
    const [cancelReason, setCancelReason] = useState('');
    const [submittingCancel, setSubmittingCancel] = useState(false);
    const [cancelError, setCancelError] = useState<ApiError | null>(null);

    const loadSummary = useCallback(async (signal?: AbortSignal) => {
        setLoadingSummary(true);
        try {
            const data = await getRentalBillingSummary(signal);
            setSummary(data);
        } catch {
            // Non-critical
        } finally {
            setLoadingSummary(false);
        }
    }, []);

    const loadDocuments = useCallback(async (signal?: AbortSignal) => {
        setLoadingList(true);
        setError(null);
        try {
            const data = await listRentalBillingDocuments(
                {
                    direction: 'outbound',
                    search: search.trim() || undefined,
                    party_id: selectedCustomer ? selectedCustomer.id : undefined,
                    status: statusFilter || undefined,
                    balance_status: balanceFilter || undefined,
                    date_from: dateFrom || undefined,
                    date_to: dateTo || undefined,
                    page,
                    per_page: 25,
                },
                signal
            );
            setPaginated(data);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoadingList(false);
        }
    }, [search, selectedCustomer, statusFilter, balanceFilter, dateFrom, dateTo, page]);

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

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setPage(1);
        loadDocuments();
    };

    const handleOpenEdit = (doc: RentalBillingDocument) => {
        setEditingDoc(doc);
        setEditDueDate(doc.due_date || '');
        setEditNotes(doc.notes || '');
        setEditError(null);
    };

    const handleSaveEdit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingDoc) return;
        setSubmittingEdit(true);
        setEditError(null);
        try {
            await updateRentalBillingDocument(editingDoc.id, {
                expected_version: editingDoc.row_version,
                due_date: editDueDate || null,
                notes: editNotes || null,
            });
            setEditingDoc(null);
            loadDocuments();
            loadSummary();
        } catch (err) {
            setEditError(toApiError(err));
        } finally {
            setSubmittingEdit(false);
        }
    };

    const handleConfirmCancel = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!cancellingDoc) return;
        setSubmittingCancel(true);
        setCancelError(null);
        try {
            await cancelRentalBillingDocument(cancellingDoc.id, {
                expected_version: cancellingDoc.row_version,
                reason: cancelReason,
            });
            setCancellingDoc(null);
            setCancelReason('');
            loadDocuments();
            loadSummary();
        } catch (err) {
            setCancelError(toApiError(err));
        } finally {
            setSubmittingCancel(false);
        }
    };

    const handleCreateFromQuotation = (agreementId: number, _periodFrom: string, _periodUntil: string) => {
        setPrefilledAgreementId(agreementId);
        setInvoiceCreateOpen(true);
    };

    return (
        <div className="space-y-6 p-6">
            {/* Header Section */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Customer Invoices (AR)</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        <span>Accounts Receivable</span> — Manage rental billing, stage-1 base charges, stage-2 telematics reconciliations, and quotation previews.
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        variant="secondary"
                        onClick={() => setQuotationPreviewOpen(true)}
                        className="border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                    >
                        Preview Quotation / Pro-Forma
                    </Button>
                    {canCustomerBill && (
                        <Button
                            variant="primary"
                            onClick={() => {
                                setPrefilledAgreementId(undefined);
                                setInvoiceCreateOpen(true);
                            }}
                        >
                            + Create Customer Invoice
                        </Button>
                    )}
                </div>
            </div>

            {/* Financial Summary Cards */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Invoiced (AR)</span>
                    <div className="mt-2 text-2xl font-extrabold text-slate-900">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.customer_ar.total_invoiced || '0.00'} />}
                    </div>
                    <span className="text-xs text-slate-400 mt-1 block">All active billed charges</span>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Unpaid Balance</span>
                    <div className="mt-2 text-2xl font-extrabold text-amber-600">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.customer_ar.total_outstanding || '0.00'} />}
                    </div>
                    <span className="text-xs text-slate-400 mt-1 block">Receivable balance remaining</span>
                </div>

                <div className="rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-rose-700">Overdue</span>
                    <div className="mt-2 text-2xl font-extrabold text-rose-700">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.customer_ar.overdue_amount || '0.00'} />}
                    </div>
                    <span className="text-xs text-rose-600 mt-1 block">
                        {summary?.customer_ar.overdue_count || 0} invoice(s) past due date
                    </span>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-blue-700">Draft Invoices</span>
                    <div className="mt-2 text-2xl font-extrabold text-blue-800">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.customer_ar.draft_amount || '0.00'} />}
                    </div>
                    <span className="text-xs text-blue-600 mt-1 block">
                        {summary?.customer_ar.draft_count ?? 0} draft(s) pending review
                    </span>
                </div>
            </div>

            {/* Quick Search & Filters */}
            <form onSubmit={handleSearchSubmit} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-6">
                    <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold text-slate-600 mb-1">Search Invoices</label>
                        <Input
                            placeholder="Invoice #, vehicle reg, agreement ref..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>

                    <div className="sm:col-span-1">
                        <LookupSelect
                            label="Customer"
                            value={selectedCustomer}
                            onChange={val => {
                                setSelectedCustomer(val);
                                setPage(1);
                            }}
                            search={lookupApi.customers}
                            loadOnOpen
                            minSearchLength={0}
                            placeholder="All Customers"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1">Invoice Status</label>
                        <Select
                            value={statusFilter}
                            onChange={e => {
                                setStatusFilter(e.target.value);
                                setPage(1);
                            }}
                            options={STATUS_OPTIONS}
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1">Balance Status</label>
                        <Select
                            value={balanceFilter}
                            onChange={e => {
                                setBalanceFilter(e.target.value);
                                setPage(1);
                            }}
                            options={BALANCE_STATUS_OPTIONS}
                        />
                    </div>

                    <div className="flex items-end gap-2">
                        <Button type="submit" variant="primary" className="w-full">
                            Filter
                        </Button>
                        {(search || selectedCustomer || statusFilter || balanceFilter || dateFrom || dateTo) && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setSearch('');
                                    setSelectedCustomer(null);
                                    setStatusFilter('');
                                    setBalanceFilter('');
                                    setDateFrom('');
                                    setDateTo('');
                                    setPage(1);
                                }}
                            >
                                Reset
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-3 pt-2 text-xs text-slate-500 border-t border-slate-100">
                    <span className="font-medium text-slate-600">Date Range:</span>
                    <Input
                        type="date"
                        className="py-1 text-xs w-36"
                        value={dateFrom}
                        onChange={e => {
                            setDateFrom(e.target.value);
                            setPage(1);
                        }}
                    />
                    <span>to</span>
                    <Input
                        type="date"
                        className="py-1 text-xs w-36"
                        value={dateTo}
                        onChange={e => {
                            setDateTo(e.target.value);
                            setPage(1);
                        }}
                    />
                </div>
            </form>

            {error && <ErrorAlert error={error} />}

            {/* Invoices DataTable */}
            {loadingList ? (
                <LoadingState label="Loading customer invoices..." />
            ) : paginated.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 className="mt-3 text-base font-semibold text-slate-900">No customer invoices found</h3>
                    <p className="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        No invoices matched your filter criteria. Use the "Quotation Preview" to simulate charges or create a new invoice.
                    </p>
                    <div className="mt-4 flex justify-center gap-2">
                        <Button variant="secondary" size="sm" onClick={() => setQuotationPreviewOpen(true)}>
                            ⚡ Try Quotation Preview
                        </Button>
                        {canCustomerBill && (
                            <Button variant="primary" size="sm" onClick={() => setInvoiceCreateOpen(true)}>
                                + Create Invoice
                            </Button>
                        )}
                    </div>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead className="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                            <tr>
                                <th scope="col" className="px-4 py-3">Invoice # / Date</th>
                                <th scope="col" className="px-4 py-3">Customer</th>
                                <th scope="col" className="px-4 py-3">Agreement & Vehicle</th>
                                <th scope="col" className="px-4 py-3">Status</th>
                                <th scope="col" className="px-4 py-3 text-right">Grand Total</th>
                                <th scope="col" className="px-4 py-3 text-right">Balance Due</th>
                                <th scope="col" className="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white text-slate-800">
                            {paginated.data.map(doc => {
                                const isDraft = doc.status === 'draft';
                                const customerName = doc.party?.name || 'Customer';
                                const vehicleLabel = doc.vehicle
                                    ? trim(`${doc.vehicle.registration_number || ''} ${doc.vehicle.make_model || ''}`)
                                    : 'Fleet Vehicle';

                                return (
                                    <tr key={doc.id} className="hover:bg-slate-50/70 transition-colors">
                                        <td className="px-4 py-3">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedDetailId(doc.id)}
                                                className="font-bold text-blue-600 hover:text-blue-800 hover:underline block text-left"
                                            >
                                                {doc.invoice_number}
                                            </button>
                                            <span className="text-slate-400 text-xs block">
                                                {doc.invoice_date || '-'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="font-semibold text-slate-900 block">{customerName}</span>
                                            {doc.party?.phone && (
                                                <span className="text-slate-400 text-xs block">{doc.party.phone}</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="font-medium text-slate-700 block">
                                                {doc.agreement?.reference || '-'}
                                            </span>
                                            <span className="text-slate-400 text-xs block truncate max-w-xs">
                                                {vehicleLabel}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-col gap-1 items-start">
                                                <StatusBadge status={doc.status} />
                                                {doc.balance_status && doc.balance_status !== 'paid' && doc.status !== 'cancelled' && (
                                                    <span className={`text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded ${
                                                        doc.balance_status === 'overdue' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'
                                                    }`}>
                                                        {doc.balance_status}
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-slate-900">
                                            <MoneyDisplay value={doc.grand_total} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <span className={`font-semibold ${Number(doc.balance_due) > 0 ? 'text-rose-600' : 'text-slate-500'}`}>
                                                <MoneyDisplay value={doc.balance_due} />
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end items-center gap-1.5">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => setSelectedDetailId(doc.id)}
                                                >
                                                    View
                                                </Button>
                                                {isDraft && canCustomerBill && (
                                                    <>
                                                        <Button
                                                            variant="secondary"
                                                            size="sm"
                                                            onClick={() => handleOpenEdit(doc)}
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            variant="danger"
                                                            size="sm"
                                                            onClick={() => {
                                                                setCancellingDoc(doc);
                                                                setCancelReason('');
                                                                setCancelError(null);
                                                            }}
                                                        >
                                                            Void
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {paginated.meta.last_page > 1 && (
                        <div className="p-4 border-t border-slate-200">
                            <Pagination
                                meta={paginated.meta}
                                onPageChange={setPage}
                            />
                        </div>
                    )}
                </div>
            )}

            {/* Detail / In-place Edit Drawer Modal */}
            <RentalBillingDocumentDetailModal
                open={Boolean(selectedDetailId)}
                onClose={() => setSelectedDetailId(null)}
                documentId={selectedDetailId}
                onDocumentUpdated={() => {
                    loadDocuments();
                    loadSummary();
                }}
            />

            {/* Direct Invoice Creation Modal */}
            <RentalInvoiceCreateModal
                open={invoiceCreateOpen}
                onClose={() => {
                    setInvoiceCreateOpen(false);
                    setPrefilledAgreementId(undefined);
                }}
                onInvoiceCreated={() => {
                    loadDocuments();
                    loadSummary();
                }}
            />

            {/* Quotation Preview Modal */}
            <RentalQuotationPreviewModal
                open={quotationPreviewOpen}
                onClose={() => setQuotationPreviewOpen(false)}
                initialAgreementId={prefilledAgreementId}
                onCreateInvoice={handleCreateFromQuotation}
            />

            {/* Direct Edit Draft Invoice Modal */}
            {editingDoc && (
                <Modal
                    open={Boolean(editingDoc)}
                    onClose={() => setEditingDoc(null)}
                    title={`Edit Draft Invoice: ${editingDoc.invoice_number}`}
                >
                    <form onSubmit={handleSaveEdit} className="space-y-4">
                        {editError && <ErrorAlert error={editError} />}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Due Date
                            </label>
                            <Input
                                type="date"
                                value={editDueDate}
                                onChange={e => setEditDueDate(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Notes
                            </label>
                            <Textarea
                                rows={3}
                                placeholder="Enter internal or invoice notes..."
                                value={editNotes}
                                onChange={e => setEditNotes(e.target.value)}
                            />
                        </div>
                        <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                            <Button variant="secondary" onClick={() => setEditingDoc(null)} disabled={submittingEdit}>
                                Cancel
                            </Button>
                            <Button
                                variant="primary"
                                type="submit"
                                loading={submittingEdit}
                            >
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </Modal>
            )}

            {/* Safe Draft Delete / Cancellation Modal */}
            {cancellingDoc && (
                <Modal
                    open={Boolean(cancellingDoc)}
                    onClose={() => setCancellingDoc(null)}
                    title={`Void Draft Invoice: ${cancellingDoc.invoice_number}`}
                >
                    <form onSubmit={handleConfirmCancel} className="space-y-4">
                        <p className="text-sm text-slate-600">
                            Are you sure you want to void draft invoice <strong className="text-slate-900">{cancellingDoc.invoice_number}</strong>?
                            Any associated billing cycle will be restored safely, allowing subsequent cycles to proceed.
                        </p>

                        {cancelError && <ErrorAlert error={cancelError} />}

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Audit Reason for Voiding <span className="text-rose-500">*</span>
                            </label>
                            <Textarea
                                rows={3}
                                placeholder="Specify why this draft is being voided..."
                                value={cancelReason}
                                onChange={e => setCancelReason(e.target.value)}
                                required
                            />
                        </div>

                        <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                            <Button variant="secondary" onClick={() => setCancellingDoc(null)} disabled={submittingCancel}>
                                Cancel
                            </Button>
                            <Button
                                variant="danger"
                                type="submit"
                                loading={submittingCancel}
                                disabled={cancelReason.trim().length < 5}
                            >
                                Confirm Void Invoice
                            </Button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}

function trim(str: string): string {
    return str.replace(/^\s+|\s+$/g, '');
}
