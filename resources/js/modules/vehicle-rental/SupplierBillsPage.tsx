import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
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
import { OwnerSettlementModal } from './OwnerSettlementModal';
import { RentalOwnerBillPreviewModal } from './RentalOwnerBillPreviewModal';

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

export default function SupplierBillsPage() {
    const auth = useAuth();
    const canOwnerView = hasPermission(auth, agreementPermissions.owner.view);
    const canOwnerBill = hasPermission(auth, BILLING_PERMISSION[AgreementKind.Owner]);

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
    const [selectedOwner, setSelectedOwner] = useState<NamedResource | null>(null);
    const [statusFilter, setStatusFilter] = useState('');
    const [balanceFilter, setBalanceFilter] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);

    // Modals
    const [selectedDetailId, setSelectedDetailId] = useState<number | null>(null);
    const [settlementModalOpen, setSettlementModalOpen] = useState(false);
    const [billPreviewOpen, setBillPreviewOpen] = useState(false);
    const [prefilledOwnerAgreementId, setPrefilledOwnerAgreementId] = useState<number | undefined>(undefined);
    const [prefilledDates, setPrefilledDates] = useState<{ start?: string; end?: string }>({});

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
                    direction: 'inbound',
                    search: search.trim() || undefined,
                    party_id: selectedOwner ? selectedOwner.id : undefined,
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
    }, [search, selectedOwner, statusFilter, balanceFilter, dateFrom, dateTo, page]);

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

    const handleGenerateFromSimulation = (agreementId: number, start: string, end: string) => {
        setPrefilledOwnerAgreementId(agreementId);
        setPrefilledDates({ start, end });
        setSettlementModalOpen(true);
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Owner & Supplier Bills (AP)"
                description={
                    <>
                        <span>Accounts Payable</span> — Manage lessor settlements, 10% statutory WHT deductions (:3305-000), maintenance chargebacks, and disbursements.
                    </>
                }
                actions={
                    <>
                        <Button
                            variant="secondary"
                            onClick={() => setBillPreviewOpen(true)}
                        >
                            Preview Pro-Forma Settlement
                        </Button>
                        {canOwnerBill && (
                            <Button
                                variant="primary"
                                onClick={() => {
                                    setPrefilledOwnerAgreementId(undefined);
                                    setPrefilledDates({});
                                    setSettlementModalOpen(true);
                                }}
                            >
                                + Generate Owner Bill
                            </Button>
                        )}
                    </>
                }
            />

            {/* Financial Summary Cards */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Billed (Gross AP)</span>
                    <div className="mt-2 text-2xl font-extrabold text-slate-900">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.owner_ap.total_gross_billed || '0.00'} />}
                    </div>
                    <span className="text-xs text-slate-400 mt-1 block">Total gross earnings before deductions</span>
                </div>

                <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-amber-800">Unsettled Payable</span>
                    <div className="mt-2 text-2xl font-extrabold text-amber-700">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.owner_ap.total_outstanding || '0.00'} />}
                    </div>
                    <span className="text-xs text-amber-600 mt-1 block">
                        Net balance pending disbursement
                    </span>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Paid / Settled</span>
                    <div className="mt-2 text-2xl font-extrabold text-emerald-600">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.owner_ap.total_net_paid || '0.00'} />}
                    </div>
                    <span className="text-xs text-slate-400 mt-1 block">Realized lessor disbursements</span>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-blue-700">Pending Draft Bills</span>
                    <div className="mt-2 text-2xl font-extrabold text-blue-800">
                        {loadingSummary ? '...' : <MoneyDisplay value={summary?.owner_ap.draft_amount || '0.00'} />}
                    </div>
                    <span className="text-xs text-blue-600 mt-1 block">
                        {summary?.owner_ap.draft_count ?? 0} draft(s) awaiting approval
                    </span>
                </div>
            </div>

            {/* Quick Search & Filters */}
            <form onSubmit={handleSearchSubmit} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-6">
                    <div className="sm:col-span-2">
                        <Input
                            label="Search Bills"
                            placeholder="Bill #, vehicle reg, agreement ref..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>

                    <div className="sm:col-span-1">
                        <LookupSelect
                            label="Owner / Supplier"
                            value={selectedOwner}
                            onChange={val => {
                                setSelectedOwner(val);
                                setPage(1);
                            }}
                            search={lookupApi.suppliers}
                            loadOnOpen
                            minSearchLength={0}
                            placeholder="All Owners"
                        />
                    </div>

                    <div>
                        <Select
                            label="Bill Status"
                            value={statusFilter}
                            onChange={e => {
                                setStatusFilter(e.target.value);
                                setPage(1);
                            }}
                            options={STATUS_OPTIONS}
                        />
                    </div>

                    <div>
                        <Select
                            label="Balance Status"
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
                        {(search || selectedOwner || statusFilter || balanceFilter || dateFrom || dateTo) && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setSearch('');
                                    setSelectedOwner(null);
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

                <div className="flex flex-wrap items-center gap-3 pt-3 text-xs text-slate-600 border-t border-slate-100">
                    <span className="font-semibold text-slate-700">Date Range:</span>
                    <div className="w-40">
                        <Input
                            type="date"
                            aria-label="From Date"
                            value={dateFrom}
                            onChange={e => {
                                setDateFrom(e.target.value);
                                setPage(1);
                            }}
                        />
                    </div>
                    <span className="text-slate-400">to</span>
                    <div className="w-40">
                        <Input
                            type="date"
                            aria-label="To Date"
                            value={dateTo}
                            onChange={e => {
                                setDateTo(e.target.value);
                                setPage(1);
                            }}
                        />
                    </div>
                </div>
            </form>

            {error && <ErrorAlert error={error} />}

            {/* Bills DataTable */}
            {loadingList ? (
                <LoadingState label="Loading supplier / owner bills..." />
            ) : paginated.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 className="mt-3 text-base font-semibold text-slate-900">No owner bills found</h3>
                    <p className="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        No supplier bills match your search criteria. You can simulate settlements or generate a new owner bill.
                    </p>
                    <div className="mt-4 flex justify-center gap-2">
                        <Button variant="secondary" size="sm" onClick={() => setBillPreviewOpen(true)}>
                            ⚡ Try Settlement Preview
                        </Button>
                        {canOwnerBill && (
                            <Button variant="primary" size="sm" onClick={() => setSettlementModalOpen(true)}>
                                + Generate Owner Bill
                            </Button>
                        )}
                    </div>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead className="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                            <tr>
                                <th scope="col" className="px-4 py-3">Bill # / Date</th>
                                <th scope="col" className="px-4 py-3">Owner / Lessor</th>
                                <th scope="col" className="px-4 py-3">Vehicle & Agreement</th>
                                <th scope="col" className="px-4 py-3 text-right">Gross Lease</th>
                                <th scope="col" className="px-4 py-3 text-right">10% WHT</th>
                                <th scope="col" className="px-4 py-3 text-right">Deductions</th>
                                <th scope="col" className="px-4 py-3 text-right">Net Payable</th>
                                <th scope="col" className="px-4 py-3 text-right">Balance Due</th>
                                <th scope="col" className="px-4 py-3">Status</th>
                                <th scope="col" className="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white text-slate-800">
                            {paginated.data.map(doc => {
                                const isDraft = doc.status === 'draft';
                                const ownerName = doc.party?.name || 'Owner / Lessor';
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
                                            <span className="font-semibold text-slate-900 block">{ownerName}</span>
                                            {doc.party?.code && (
                                                <span className="text-slate-400 text-xs block">Code: {doc.party.code}</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="font-medium text-slate-700 block">
                                                {doc.agreement?.reference || '-'}
                                            </span>
                                            <span className="text-slate-500 text-xs font-medium block">
                                                {doc.vehicle?.registration_number || 'Fleet Vehicle'}
                                            </span>
                                            {doc.vehicle?.make_model && (
                                                <span className="text-slate-400 text-[11px] block truncate max-w-xs">
                                                    {doc.vehicle.make_model}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium text-slate-700">
                                            <MoneyDisplay value={doc.subtotal || doc.gross_amount} />
                                        </td>
                                        <td className="px-4 py-3 text-right text-rose-600 font-medium">
                                            {Number(doc.wht_amount || 0) > 0 ? (
                                                <span>-<MoneyDisplay value={doc.wht_amount} /></span>
                                            ) : (
                                                <span className="text-slate-400">0.00</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right text-amber-700 font-medium">
                                            {Number(doc.maintenance_deductions || doc.discount_total || 0) > 0 ? (
                                                <span>-<MoneyDisplay value={doc.maintenance_deductions || doc.discount_total || '0.00'} /></span>
                                            ) : (
                                                <span className="text-slate-400">0.00</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right font-bold text-emerald-700">
                                            <MoneyDisplay value={doc.grand_total} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <span className={`font-semibold ${Number(doc.balance_due) > 0 ? 'text-rose-600' : 'text-slate-500'}`}>
                                                <MoneyDisplay value={doc.balance_due} />
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
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end items-center gap-1.5">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => setSelectedDetailId(doc.id)}
                                                >
                                                    View
                                                </Button>
                                                {isDraft && canOwnerBill && (
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

            {/* Direct Owner Settlement Modal */}
            <OwnerSettlementModal
                open={settlementModalOpen}
                onClose={() => {
                    setSettlementModalOpen(false);
                    setPrefilledOwnerAgreementId(undefined);
                    setPrefilledDates({});
                }}
                ownerAgreementId={prefilledOwnerAgreementId}
                defaultStartDate={prefilledDates.start}
                defaultEndDate={prefilledDates.end}
                onBillGenerated={() => {
                    loadDocuments();
                    loadSummary();
                }}
            />

            {/* Owner Bill Simulation Preview Modal */}
            <RentalOwnerBillPreviewModal
                open={billPreviewOpen}
                onClose={() => setBillPreviewOpen(false)}
                initialAgreementId={prefilledOwnerAgreementId}
                onProceedToGenerateBill={(agreementId, start, end) => handleGenerateFromSimulation(agreementId, start, end)}
            />

            {/* Direct Edit Draft Bill Modal */}
            {editingDoc && (
                <Modal
                    open={Boolean(editingDoc)}
                    onClose={() => setEditingDoc(null)}
                    title={`Edit Draft Bill: ${editingDoc.invoice_number}`}
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
                                placeholder="Enter internal or bill settlement notes..."
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
                    title={`Void Draft Bill: ${cancellingDoc.invoice_number}`}
                >
                    <form onSubmit={handleConfirmCancel} className="space-y-4">
                        <p className="text-sm text-slate-600">
                            Are you sure you want to void draft bill <strong className="text-slate-900">{cancellingDoc.invoice_number}</strong>?
                            Any associated owner billing cycles and expense allocations will be restored cleanly.
                        </p>

                        {cancelError && <ErrorAlert error={cancelError} />}

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Audit Reason for Voiding <span className="text-rose-500">*</span>
                            </label>
                            <Textarea
                                rows={3}
                                placeholder="Specify why this draft settlement is being voided..."
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
                                Confirm Void Bill
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
