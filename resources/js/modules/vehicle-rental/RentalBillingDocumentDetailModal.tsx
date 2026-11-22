/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useState, useEffect } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { getInvoiceSignedPrintLink } from '@/modules/invoice/invoiceApi';
import { AgreementKind, type Agreement } from './agreements';
import { getAgreement } from './agreementApi';
import { AgreementPaymentModal } from './AgreementPaymentModal';
import {
    getRentalBillingDetail,
    updateRentalBillingDocument,
    cancelRentalBillingDocument,
    type RentalBillingDetail,
} from './rentalBillingApi';

interface Props {
    open: boolean;
    onClose: () => void;
    documentId: number | null;
    onDocumentUpdated?: () => void;
}

export function RentalBillingDocumentDetailModal({
    open,
    onClose,
    documentId,
    onDocumentUpdated,
}: Props) {
    const [detail, setDetail] = useState<RentalBillingDetail | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    // Editing draft
    const [editing, setEditing] = useState(false);
    const [editDueDate, setEditDueDate] = useState('');
    const [editNotes, setEditNotes] = useState('');
    const [savingEdit, setSavingEdit] = useState(false);

    // Cancellation prompt
    const [cancelling, setCancelling] = useState(false);
    const [cancelReason, setCancelReason] = useState('');
    const [submittingCancel, setSubmittingCancel] = useState(false);

    // Printing
    const [printing, setPrinting] = useState(false);

    // Recording Payment
    const [openPaymentModal, setOpenPaymentModal] = useState(false);
    const [linkedAgreement, setLinkedAgreement] = useState<Agreement | null>(null);
    const [loadingPaymentContext, setLoadingPaymentContext] = useState(false);

    useEffect(() => {
        if (!open || !documentId) {
            setDetail(null);
            setEditing(false);
            setCancelling(false);
            setError(null);
            return;
        }

        let active = true;
        setLoading(true);
        setError(null);

        getRentalBillingDetail(documentId)
            .then(data => {
                if (active) {
                    setDetail(data);
                    setEditDueDate(data.due_date ?? '');
                    setEditNotes(data.notes ?? '');
                }
            })
            .catch(err => {
                if (active) {
                    setError(toApiError(err));
                }
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [open, documentId]);

    async function handleSaveEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!detail) return;

        setSavingEdit(true);
        setError(null);

        try {
            const updated = await updateRentalBillingDocument(detail.id, {
                expected_version: detail.row_version,
                due_date: editDueDate || null,
                notes: editNotes || null,
            });
            setDetail(updated);
            setEditing(false);
            if (onDocumentUpdated) {
                onDocumentUpdated();
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSavingEdit(false);
        }
    }

    async function handleConfirmCancel(e: React.FormEvent) {
        e.preventDefault();
        if (!detail) return;

        if (cancelReason.trim().length < 5) {
            setError(toApiError(new Error('Cancellation reason must be at least 5 characters.')));
            return;
        }

        setSubmittingCancel(true);
        setError(null);

        try {
            const updated = await cancelRentalBillingDocument(detail.id, {
                expected_version: detail.row_version,
                reason: cancelReason.trim(),
            });
            setDetail(updated);
            setCancelling(false);
            if (onDocumentUpdated) {
                onDocumentUpdated();
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmittingCancel(false);
        }
    }

    async function handlePrint(format: 'print' | 'pdf' = 'print') {
        if (!detail) return;
        setPrinting(true);
        try {
            const res = await getInvoiceSignedPrintLink(detail.id);
            if (format === 'pdf' && res.pdf_url) {
                window.open(res.pdf_url, '_blank', 'noopener');
                return;
            }
            if (res.print_url) {
                window.open(res.print_url, '_blank', 'noopener');
                return;
            }
            window.open(format === 'pdf' ? `/invoices/${detail.id}/pdf` : `/invoices/${detail.id}/print`, '_blank', 'noopener');
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setPrinting(false);
        }
    }

    async function handleLaunchPayment() {
        if (!detail || !detail.agreement?.id) {
            setError(toApiError(new Error('This document does not have a linked agreement for payment allocation.')));
            return;
        }

        setLoadingPaymentContext(true);
        setError(null);

        try {
            const kind = detail.direction === 'outbound' ? AgreementKind.Customer : AgreementKind.Owner;
            const ag = await getAgreement(kind, detail.agreement.id);
            setLinkedAgreement(ag);
            setOpenPaymentModal(true);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoadingPaymentContext(false);
        }
    }

    if (!open) return null;

    const title = detail
        ? `${detail.direction === 'outbound' ? 'Customer Invoice' : 'Vehicle Owner Bill'} · ${detail.invoice_number}`
        : 'Billing Document Details';

    return (
        <>
            <Modal
                open={open}
                title={title}
                onClose={onClose}
                closeDisabled={savingEdit || submittingCancel || printing || loadingPaymentContext}
            >
            {loading && <LoadingState label="Loading document details..." />}

            {!loading && detail && (
                <div className="space-y-6">
                    <ErrorAlert error={error} inline />

                    {/* Document Header & Status Strip */}
                    <div className="flex flex-wrap items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="font-mono text-base font-bold text-slate-900">
                                    {detail.invoice_number}
                                </span>
                                <span className="text-xs uppercase px-2 py-0.5 rounded font-semibold bg-slate-200 text-slate-700">
                                    {detail.direction === 'outbound' ? 'Accounts Receivable' : 'Accounts Payable'}
                                </span>
                            </div>
                            <div className="text-xs text-slate-500">
                                Issued: <span className="font-medium text-slate-700">{detail.invoice_date ?? '-'}</span>
                                {detail.due_date && (
                                    <> &bull; Due: <span className="font-medium text-slate-700">{detail.due_date}</span></>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <StatusBadge status={detail.status} />
                            <StatusBadge
                                status={detail.balance_status}
                                label={
                                    detail.balance_status === 'paid'
                                        ? 'Fully Paid'
                                        : detail.balance_status === 'overdue'
                                        ? 'Overdue'
                                        : detail.balance_status === 'partially_paid'
                                        ? 'Partially Paid'
                                        : detail.balance_status === 'cancelled'
                                        ? 'Cancelled'
                                        : 'Unpaid'
                                }
                            />
                        </div>
                    </div>

                    {/* Cancellation Alert if Cancelled */}
                    {detail.status === 'cancelled' && (
                        <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs">
                            <div className="font-bold">This document has been cancelled / voided.</div>
                            {detail.cancellation_reason && (
                                <div className="mt-1">
                                    <span className="font-semibold">Reason:</span> {detail.cancellation_reason}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Party & Agreement Context Card */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                {detail.direction === 'outbound' ? 'Customer Information' : 'Vehicle Owner Information'}
                            </h4>
                            <div className="font-semibold text-slate-900">{detail.party.name}</div>
                            <div className="text-xs text-slate-500 flex flex-wrap gap-x-4">
                                <span>Code: <span className="text-slate-700">{detail.party.code}</span></span>
                                {detail.party.phone && (
                                    <span>Phone: <span className="text-slate-700">{detail.party.phone}</span></span>
                                )}
                            </div>
                        </div>

                        <div className="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Rental & Vehicle Context
                            </h4>
                            <div className="text-xs space-y-1">
                                <div>
                                    <span className="text-slate-500">Agreement Ref:</span>{' '}
                                    <span className="font-medium text-slate-800">
                                        {detail.agreement?.reference ?? 'Direct Billing'}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500">Vehicle:</span>{' '}
                                    <span className="font-medium text-slate-800">
                                        {detail.vehicle?.registration_number
                                            ? `${detail.vehicle.registration_number} (${detail.vehicle.make_model ?? 'Vehicle'})`
                                            : 'Fleet / Multiple'}
                                    </span>
                                </div>
                                {detail.driver_name && (
                                    <div>
                                        <span className="text-slate-500">Assigned Driver:</span>{' '}
                                        <span className="font-medium text-slate-800">{detail.driver_name}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Telematics & Operations Summary Strip */}
                    {detail.telematics && detail.telematics.has_charts && (
                        <div className="p-4 rounded-xl border border-indigo-100 bg-indigo-50/50 space-y-2">
                            <div className="flex items-center justify-between">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-900">
                                    Operational & Telematics Summary
                                </h4>
                                <span className="text-2xs font-semibold px-2 py-0.5 rounded bg-indigo-100 text-indigo-800">
                                    {detail.telematics.trips_count} Running {detail.telematics.trips_count === 1 ? 'Chart' : 'Charts'} Attached
                                </span>
                            </div>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div className="p-2.5 rounded-lg bg-white border border-indigo-100/80">
                                    <div className="text-2xs text-slate-500 font-medium">Commercial Distance</div>
                                    <div className="font-mono font-bold text-slate-900 text-sm mt-0.5">
                                        {Number(detail.telematics.commercial_km).toLocaleString()} km
                                    </div>
                                </div>
                                <div className="p-2.5 rounded-lg bg-white border border-indigo-100/80">
                                    <div className="text-2xs text-slate-500 font-medium">Normal OT (1.5x)</div>
                                    <div className="font-mono font-bold text-slate-900 text-sm mt-0.5">
                                        {detail.telematics.normal_ot_hours} hrs
                                    </div>
                                </div>
                                <div className="p-2.5 rounded-lg bg-white border border-indigo-100/80">
                                    <div className="text-2xs text-slate-500 font-medium">Double OT (2.0x)</div>
                                    <div className="font-mono font-bold text-slate-900 text-sm mt-0.5">
                                        {detail.telematics.double_ot_hours} hrs
                                    </div>
                                </div>
                                <div className="p-2.5 rounded-lg bg-white border border-indigo-100/80">
                                    <div className="text-2xs text-slate-500 font-medium">Night Outs</div>
                                    <div className="font-mono font-bold text-slate-900 text-sm mt-0.5">
                                        {detail.telematics.night_outs} nights
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Itemized Lines */}
                    <div className="space-y-2">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">Itemized Charges</h4>
                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <table className="min-w-full text-xs text-left">
                                <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th className="py-2.5 px-3 w-12 text-center">#</th>
                                        <th className="py-2.5 px-3">Description</th>
                                        <th className="py-2.5 px-3 text-right">Qty</th>
                                        <th className="py-2.5 px-3 text-right">Unit Price</th>
                                        {detail.lines.some(l => Number(l.discount_amount) > 0) && (
                                            <th className="py-2.5 px-3 text-right">Discount</th>
                                        )}
                                        <th className="py-2.5 px-3 text-right">Tax</th>
                                        <th className="py-2.5 px-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-800">
                                    {detail.lines.map((line, idx) => {
                                        const hasLineDiscounts = detail.lines.some(l => Number(l.discount_amount) > 0);
                                        return (
                                            <tr key={line.id || idx} className="hover:bg-slate-50/50">
                                                <td className="py-2 px-3 text-center text-slate-400">{line.line_number}</td>
                                                <td className="py-2 px-3 font-medium text-slate-900">{line.description}</td>
                                                <td className="py-2 px-3 text-right font-mono">{line.quantity}</td>
                                                <td className="py-2 px-3 text-right font-mono"><MoneyDisplay value={line.unit_price} /></td>
                                                {hasLineDiscounts && (
                                                    <td className="py-2 px-3 text-right font-mono text-amber-700">
                                                        {Number(line.discount_amount) > 0 ? (
                                                            <>- <MoneyDisplay value={line.discount_amount} /></>
                                                        ) : (
                                                            '-'
                                                        )}
                                                    </td>
                                                )}
                                                <td className="py-2 px-3 text-right font-mono text-slate-500"><MoneyDisplay value={line.tax_total} /></td>
                                                <td className="py-2 px-3 text-right font-mono font-semibold text-slate-900"><MoneyDisplay value={line.grand_total} /></td>
                                            </tr>
                                        );
                                    })}
                                    {detail.lines.length === 0 && (
                                        <tr>
                                            <td colSpan={detail.lines.some(l => Number(l.discount_amount) > 0) ? 7 : 6} className="py-4 text-center text-slate-400">
                                                No itemized lines recorded.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Adjustments & Deductions Section */}
                    {detail.adjustments.length > 0 && (
                        <div className="space-y-2">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Adjustments & Statutory Deductions
                            </h4>
                            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table className="min-w-full text-xs text-left">
                                    <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                        <tr>
                                            <th className="py-2 px-3">Adjustment Description</th>
                                            <th className="py-2 px-3">Type</th>
                                            <th className="py-2 px-3 text-center">Effect</th>
                                            <th className="py-2 px-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 text-slate-800">
                                        {detail.adjustments.map((adj, idx) => (
                                            <tr key={adj.id || idx}>
                                                <td className="py-2 px-3 font-medium">{adj.name}</td>
                                                <td className="py-2 px-3 capitalize text-slate-600">{adj.adjustment_type}</td>
                                                <td className="py-2 px-3 text-center">
                                                    <span className={`px-2 py-0.5 rounded text-2xs font-bold uppercase ${
                                                        adj.effect === 'decrease' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'
                                                    }`}>
                                                        {adj.effect}
                                                    </span>
                                                </td>
                                                <td className="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                                    {adj.effect === 'decrease' ? '- ' : '+ '}
                                                    <MoneyDisplay value={adj.amount} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Comprehensive Financial Breakdown Card */}
                    <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Comprehensive Financial Breakdown
                        </h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div className="space-y-1.5 border-b sm:border-b-0 sm:border-r border-slate-200 sm:pr-4 pb-3 sm:pb-0">
                                <div className="flex justify-between text-slate-600">
                                    <span>Base Lease / Rental Fee:</span>
                                    <span className="font-mono font-medium text-slate-800">
                                        <MoneyDisplay value={detail.financial_breakdown?.base_rent_amount ?? detail.subtotal} />
                                    </span>
                                </div>
                                {Number(detail.financial_breakdown?.excess_km_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Excess Mileage Charges:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            <MoneyDisplay value={detail.financial_breakdown.excess_km_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.overtime_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Driver Overtime Services:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            <MoneyDisplay value={detail.financial_breakdown.overtime_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.night_out_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Night-Out Allowances:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            <MoneyDisplay value={detail.financial_breakdown.night_out_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.tolls_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Tolls & Highway Charges:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            <MoneyDisplay value={detail.financial_breakdown.tolls_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.additions_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Ad-Hoc Additions:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            <MoneyDisplay value={detail.financial_breakdown.additions_amount} />
                                        </span>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-1.5 sm:pl-2">
                                {Number(detail.financial_breakdown?.deductions_amount) > 0 && (
                                    <div className="flex justify-between text-amber-700">
                                        <span>Contractual Deductions & Discounts:</span>
                                        <span className="font-mono font-medium">
                                            - <MoneyDisplay value={detail.financial_breakdown.deductions_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.wht_withheld ?? detail.wht_amount) > 0 && (
                                    <div className="flex justify-between text-amber-700">
                                        <span>Statutory Withholding Tax (10% WHT to :3305-000):</span>
                                        <span className="font-mono font-medium">
                                            - <MoneyDisplay value={detail.financial_breakdown?.wht_withheld ?? detail.wht_amount} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.financial_breakdown?.maintenance_deductions) > 0 && (
                                    <div className="flex justify-between text-amber-700">
                                        <span>Fleet Maintenance Deductions:</span>
                                        <span className="font-mono font-medium">
                                            - <MoneyDisplay value={detail.financial_breakdown.maintenance_deductions} />
                                        </span>
                                    </div>
                                )}
                                {Number(detail.tax_total) > 0 && (
                                    <div className="flex justify-between text-slate-600">
                                        <span>Applicable Tax Total:</span>
                                        <span className="font-mono font-medium text-slate-800">
                                            + <MoneyDisplay value={detail.tax_total} />
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between text-sm font-bold text-slate-900 pt-2 border-t border-slate-200">
                                    <span>{detail.direction === 'outbound' ? 'Grand Total Due:' : 'Net Payable to Owner:'}</span>
                                    <span className="font-mono text-emerald-700"><MoneyDisplay value={detail.grand_total} /></span>
                                </div>
                                <div className="flex justify-between text-slate-600 pt-0.5">
                                    <span>Total Paid / Allocated:</span>
                                    <span className="font-mono font-medium text-emerald-600"><MoneyDisplay value={detail.paid_total} /></span>
                                </div>
                                <div className="flex justify-between text-sm font-bold pt-1 border-t border-slate-200">
                                    <span>Outstanding Balance:</span>
                                    <span className={`font-mono ${Number(detail.balance_due) > 0 ? 'text-rose-600 font-extrabold' : 'text-slate-500'}`}>
                                        <MoneyDisplay value={detail.balance_due} />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Payment History & Allocations */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Payment Allocations & Receipts
                            </h4>
                            {detail.can_record_payment && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={handleLaunchPayment}
                                    loading={loadingPaymentContext}
                                >
                                    + Record Payment
                                </Button>
                            )}
                        </div>

                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table className="min-w-full text-xs text-left">
                                <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th className="py-2 px-3">Date</th>
                                        <th className="py-2 px-3">Payment #</th>
                                        <th className="py-2 px-3">Method</th>
                                        <th className="py-2 px-3">Reference</th>
                                        <th className="py-2 px-3 text-right">Allocated Amount</th>
                                        <th className="py-2 px-3 text-center">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-800">
                                    {detail.payments.map((p, idx) => (
                                        <tr key={p.id || idx}>
                                            <td className="py-2 px-3 text-slate-600">{p.payment_date ?? '-'}</td>
                                            <td className="py-2 px-3 font-mono font-medium text-slate-900">{p.payment_number}</td>
                                            <td className="py-2 px-3 capitalize">{p.method_name}</td>
                                            <td className="py-2 px-3 text-slate-500">{p.reference}</td>
                                            <td className="py-2 px-3 text-right font-mono font-bold text-emerald-700">
                                                <MoneyDisplay value={p.allocated_amount} />
                                            </td>
                                            <td className="py-2 px-3 text-center">
                                                {p.receipt_url && (
                                                    <button
                                                        type="button"
                                                        onClick={() => window.open(p.receipt_url!, '_blank')}
                                                        className="text-xs text-blue-600 hover:text-blue-800 font-medium underline"
                                                    >
                                                        Print Receipt
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {detail.payments.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="py-4 text-center text-slate-400">
                                                No payments have been allocated to this document yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Inline Edit Form for Draft Document */}
                    {editing && (
                        <form onSubmit={handleSaveEdit} className="p-4 rounded-xl border border-blue-200 bg-blue-50/50 space-y-3">
                            <h4 className="text-xs font-bold text-blue-900">Edit Draft Terms</h4>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <Input
                                    label="Payment Due Date"
                                    type="date"
                                    value={editDueDate}
                                    onChange={e => setEditDueDate(e.target.value)}
                                />
                            </div>
                            <Textarea
                                label="Internal Notes"
                                value={editNotes}
                                onChange={e => setEditNotes(e.target.value)}
                                rows={2}
                            />
                            <div className="flex justify-end gap-2 pt-1">
                                <Button type="button" variant="secondary" onClick={() => setEditing(false)} disabled={savingEdit}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" loading={savingEdit}>
                                    Save Changes
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Cancellation Form Prompt */}
                    {cancelling && (
                        <form onSubmit={handleConfirmCancel} className="p-4 rounded-xl border border-rose-200 bg-rose-50 space-y-3">
                            <h4 className="text-xs font-bold text-rose-900">Void / Cancel Document</h4>
                            <p className="text-xs text-rose-800">
                                Cancelling will mark this document as void and release any reserved running charts or charges. A mandatory reason is required for accounting audit compliance.
                            </p>
                            <Input
                                label="Mandatory Reason for Cancellation"
                                value={cancelReason}
                                onChange={e => setCancelReason(e.target.value)}
                                placeholder="E.g. Customer cancelled rental agreement, rate adjustment..."
                                required
                            />
                            <div className="flex justify-end gap-2 pt-1">
                                <Button type="button" variant="secondary" onClick={() => setCancelling(false)} disabled={submittingCancel}>
                                    Back
                                </Button>
                                <Button type="submit" variant="danger" loading={submittingCancel}>
                                    Confirm Cancellation
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Footer Actions */}
                    <div className="flex flex-wrap items-center justify-between gap-2 pt-4 border-t border-slate-200">
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => handlePrint('print')}
                                loading={printing}
                            >
                                Print
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => handlePrint('pdf')}
                                loading={printing}
                            >
                                PDF
                            </Button>
                            {detail.statement_url && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => window.open(detail.statement_url + '?format=print', '_blank', 'noopener')}
                                >
                                    Statement
                                </Button>
                            )}
                            {detail.can_edit && !editing && !cancelling && (
                                <Button type="button" variant="secondary" onClick={() => setEditing(true)}>
                                    Edit Draft
                                </Button>
                            )}
                            {detail.can_cancel && !cancelling && (
                                <Button type="button" variant="danger" onClick={() => setCancelling(true)}>
                                    Cancel / Void
                                </Button>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            {detail.can_record_payment && (
                                <Button
                                    type="button"
                                    variant="primary"
                                    onClick={handleLaunchPayment}
                                    loading={loadingPaymentContext}
                                >
                                    Record Payment
                                </Button>
                            )}
                            <Button type="button" variant="secondary" onClick={onClose}>
                                Close
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </Modal>

        {openPaymentModal && linkedAgreement && detail && (
            <AgreementPaymentModal
                open={openPaymentModal}
                onClose={() => {
                    setOpenPaymentModal(false);
                    if (detail.id) {
                        getRentalBillingDetail(detail.id).then(setDetail);
                    }
                    if (onDocumentUpdated) {
                        onDocumentUpdated();
                    }
                }}
                kind={detail.direction === 'outbound' ? AgreementKind.Customer : AgreementKind.Owner}
                agreement={linkedAgreement}
                initialInvoiceId={detail.id}
                openInvoices={[
                    {
                        id: detail.id,
                        invoice_number: detail.invoice_number,
                        invoice_date: detail.invoice_date ?? '',
                        due_date: detail.due_date,
                        invoice_type: detail.invoice_type,
                        document_kind: detail.direction,
                        status: detail.status,
                        grand_total: detail.grand_total,
                        paid_total: detail.paid_total,
                        balance_due: detail.balance_due,
                    },
                ]}
                onPaymentSuccess={() => {
                    setOpenPaymentModal(false);
                    if (detail.id) {
                        getRentalBillingDetail(detail.id).then(setDetail);
                    }
                    if (onDocumentUpdated) {
                        onDocumentUpdated();
                    }
                }}
            />
        )}
        </>
    );
}
