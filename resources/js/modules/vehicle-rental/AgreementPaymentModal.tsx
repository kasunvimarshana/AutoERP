import React, { useEffect, useRef, useState } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue, formatBusinessDate } from '@/shared/utils/businessDate';
import { compareDecimalStrings, isPositiveDecimal, sumDecimals } from '@/shared/utils/decimal';
import { useApi } from '@/shared/hooks/useApi';
import { listUsablePaymentMethods } from '@/modules/payment/paymentApi';
import { PaymentLineTable, type PaymentLineDraft } from '@/modules/payment/components/PaymentLineTable';
import { linePayload, lineIsValid } from '@/modules/payment/paymentLineInput';
import { AgreementKind, type Agreement } from './agreements';
import {
    recordAgreementPayment,
    allocateAgreementPayment,
    getPartyOpenRentalDocuments,
    recordPartyRentalPayment,
    allocatePartyRentalPayment,
    getPaymentReceiptPrintUrl,
    type AgreementPayment,
    type PartyOpenDocument,
    type RecordRentalPaymentPayload,
} from './agreementApi';

interface OpenInvoiceDoc {
    id: number;
    invoice_number: string;
    invoice_date: string;
    due_date?: string | null;
    invoice_type: string;
    document_kind: string;
    status: string;
    grand_total: string;
    paid_total: string;
    balance_due: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    kind: AgreementKind;
    agreement: Agreement;
    openInvoices?: OpenInvoiceDoc[];
    initialInvoiceId?: number | null;
    initialPayment?: AgreementPayment | null;
    onPaymentSuccess: () => void;
}

const FIRST_LINE = 1;
const METHOD_PAGE_SIZE = 100;

const emptyLine = (key: number): PaymentLineDraft => ({
    key,
    paymentMethodId: '',
    amount: '',
    reference: '',
    metadata: {},
});

export function AgreementPaymentModal({
    open,
    onClose,
    kind,
    agreement,
    openInvoices = [],
    initialInvoiceId,
    initialPayment,
    onPaymentSuccess,
}: Props) {
    const isCustomer = kind === AgreementKind.Customer;
    const direction = isCustomer ? 'inbound' : 'outbound';
    const isAllocateMode = Boolean(initialPayment);
    const cur = agreement.currency?.code ?? 'USD';

    const [paymentDate, setPaymentDate] = useState(businessDateInputValue);
    const [exchangeRate, setExchangeRate] = useState('1.000000');
    const [referenceNumber, setReferenceNumber] = useState('');
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(FIRST_LINE)]);
    const nextLineKey = useRef(FIRST_LINE + 1);

    // Allocations map: invoiceId -> amount string
    const [allocations, setAllocations] = useState<Record<number, string>>({});
    const [showCrossAgreement, setShowCrossAgreement] = useState(false);

    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [successPaymentId, setSuccessPaymentId] = useState<number | null>(null);

    // Fetch usable payment methods
    const methodsApi = useApi(
        signal => listUsablePaymentMethods({ direction, per_page: METHOD_PAGE_SIZE }, signal),
        [direction],
        open && !isAllocateMode
    );
    const paymentMethods = methodsApi.data?.data ?? [];

    const partyDocsApi = useApi(
        signal => getPartyOpenRentalDocuments(kind, agreement.party.id),
        [kind, agreement.party.id],
        open && showCrossAgreement
    );

    const crossInvoices: Array<OpenInvoiceDoc & { agreement_reference?: string | null; vehicle_registration?: string | null }> = (partyDocsApi.data?.documents ?? []).map((doc: PartyOpenDocument) => ({
        id: doc.id,
        invoice_number: doc.invoice_number,
        invoice_date: doc.invoice_date ?? '',
        due_date: doc.due_date,
        invoice_type: doc.invoice_type,
        document_kind: 'invoice',
        status: doc.status,
        grand_total: doc.total_amount,
        paid_total: '0.00',
        balance_due: doc.remaining_amount,
        agreement_reference: doc.agreement_reference,
        vehicle_registration: doc.vehicle_registration,
    }));

    const sourceInvoices = showCrossAgreement ? crossInvoices : openInvoices;

    // Filter candidate invoices with positive balance
    const eligibleInvoices = sourceInvoices.filter(
        d => d.invoice_type !== 'credit' && Number(d.balance_due) > 0 && d.status !== 'draft' && d.status !== 'voided'
    );

    // Initialize allocations on modal open or when initialInvoiceId is passed
    useEffect(() => {
        if (!open) {
            setSuccessPaymentId(null);
            setError(null);
            setAllocations({});
            return;
        }

        if (initialInvoiceId) {
            const target = eligibleInvoices.find(inv => inv.id === initialInvoiceId);
            if (target) {
                setAllocations({ [target.id]: target.balance_due });
                // Also default line amount if empty
                setLines([{
                    key: FIRST_LINE,
                    paymentMethodId: '',
                    amount: target.balance_due,
                    reference: '',
                    metadata: {},
                }]);
            }
        } else if (isAllocateMode && initialPayment) {
            // Allocate mode defaults
            setAllocations({});
        } else {
            setAllocations({});
            setLines([emptyLine(FIRST_LINE)]);
        }
    }, [open, initialInvoiceId, isAllocateMode, initialPayment]);

    // Payment totals calculation
    const totalLinesAmount = isAllocateMode
        ? (initialPayment?.unapplied_amount ?? '0.000000')
        : sumDecimals(lines.map(l => l.amount || '0'));

    const totalAllocatedAmount = sumDecimals(
        Object.values(allocations).filter(v => isPositiveDecimal(v))
    );

    const isLinesValid = isAllocateMode || (
        lines.length > 0 &&
        lines.every(line => lineIsValid(line, paymentMethods.find(m => String(m.id) === line.paymentMethodId), '', direction))
    );

    const canSubmit = isAllocateMode
        ? isPositiveDecimal(totalAllocatedAmount) && compareDecimalStrings(totalAllocatedAmount, totalLinesAmount) <= 0
        : isPositiveDecimal(totalLinesAmount) &&
          isPositiveDecimal(exchangeRate) &&
          compareDecimalStrings(totalAllocatedAmount, totalLinesAmount) <= 0 &&
          isLinesValid;

    function handleAllocationChange(invoiceId: number, val: string) {
        setAllocations(prev => ({
            ...prev,
            [invoiceId]: val,
        }));
    }

    function handlePayFull(inv: OpenInvoiceDoc) {
        setAllocations(prev => ({
            ...prev,
            [inv.id]: inv.balance_due,
        }));
    }

    function handlePayAll() {
        const next: Record<number, string> = {};
        for (const inv of eligibleInvoices) {
            next[inv.id] = inv.balance_due;
        }
        setAllocations(next);

        // Conveniently set lines amount to total if single line exists
        if (lines.length === 1 && (!lines[0].amount || lines[0].amount === '0')) {
            const total = sumDecimals(Object.values(next));
            setLines([{ ...lines[0], amount: total }]);
        }
    }

    function handleClearAllocations() {
        setAllocations({});
    }

    function handleFillLineTotal() {
        if (lines.length === 1) {
            setLines([{ ...lines[0], amount: totalAllocatedAmount }]);
        }
    }

    function handleAddLine() {
        const key = nextLineKey.current++;
        setLines(prev => [...prev, emptyLine(key)]);
    }

    function handleRemoveLine(key: number) {
        setLines(prev => prev.filter(l => l.key !== key));
    }

    function handleLineChange(key: number, patch: Partial<PaymentLineDraft>) {
        setLines(prev => prev.map(l => (l.key === key ? { ...l, ...patch } : l)));
    }

    function handleMetadataChange(key: number, field: string, val: string) {
        setLines(prev => prev.map(l => {
            if (l.key !== key) return l;
            return {
                ...l,
                metadata: {
                    ...l.metadata,
                    [field]: val,
                },
            };
        }));
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!canSubmit || submitting) return;

        setSubmitting(true);
        setError(null);

        try {
            const allocationItems = Object.entries(allocations)
                .filter(([_, amt]) => isPositiveDecimal(amt))
                .map(([invId, amt]) => ({
                    invoice_id: Number(invId),
                    amount: amt,
                }));

            if (isAllocateMode && initialPayment) {
                if (showCrossAgreement) {
                    await allocatePartyRentalPayment(kind, agreement.party.id, initialPayment.id, {
                        allocations: allocationItems,
                        allocation_date: paymentDate,
                    });
                } else {
                    await allocateAgreementPayment(kind, agreement.id, initialPayment.id, {
                        allocations: allocationItems,
                        allocation_date: paymentDate,
                    });
                }
                onPaymentSuccess();
                onClose();
            } else {
                const lineData = lines.map(line =>
                    linePayload(line, paymentMethods.find(m => String(m.id) === line.paymentMethodId)!, direction)
                );

                const payload: RecordRentalPaymentPayload = {
                    expected_version: agreement.row_version,
                    agreement_id: agreement.id,
                    agreement_reference: agreement.reference,
                    currency_id: agreement.currency?.id ?? null,
                    payment_date: paymentDate,
                    exchange_rate: exchangeRate,
                    reference_number: referenceNumber.trim() || null,
                    notes: notes.trim() || null,
                    lines: lineData as any,
                    allocations: allocationItems.length > 0 ? allocationItems : undefined,
                };

                const res = showCrossAgreement
                    ? await recordPartyRentalPayment(kind, agreement.party.id, payload)
                    : await recordAgreementPayment(kind, agreement.id, payload);
                setSuccessPaymentId(res.id);
                onPaymentSuccess();
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    const modalTitle = isAllocateMode
        ? `Allocate Advance Credit · ${initialPayment?.payment_number}`
        : isCustomer
            ? `Receive Customer Payment / Advance · ${agreement.reference}`
            : `Disburse Owner Payment · ${agreement.reference}`;

    return (
        <Modal open={open} title={modalTitle} onClose={onClose} closeDisabled={submitting}>
            <div className="space-y-5 max-w-4xl max-h-[80vh] overflow-y-auto p-1">
                <ErrorAlert error={error} inline />

                {successPaymentId ? (
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center space-y-4">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-xl font-bold">
                            ✓
                        </div>
                        <h4 className="text-lg font-bold text-slate-900">
                            {isCustomer ? 'Payment Receipt Recorded Successfully!' : 'Disbursement Recorded Successfully!'}
                        </h4>
                        <p className="text-sm text-slate-600">
                            The transaction was posted and applied to the rental ledger.
                        </p>
                        <div className="flex justify-center gap-3 pt-2">
                            <Button
                                variant="secondary"
                                onClick={() => window.open(getPaymentReceiptPrintUrl(successPaymentId), '_blank')}
                            >
                                🖨️ Print Receipt / Voucher
                            </Button>
                            <Button variant="primary" onClick={onClose}>
                                Done
                            </Button>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Transaction Header Info */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <Input
                                label={isAllocateMode ? 'Allocation Date' : 'Payment Date'}
                                type="date"
                                value={paymentDate}
                                onChange={e => setPaymentDate(e.target.value)}
                                required
                            />
                            {!isAllocateMode && (
                                <>
                                    <Input
                                        label="Exchange Rate"
                                        type="number"
                                        step="0.000001"
                                        min="0.000001"
                                        value={exchangeRate}
                                        onChange={e => setExchangeRate(e.target.value)}
                                        required
                                    />
                                    <Input
                                        label="Reference Number (Cheque / Trx #)"
                                        value={referenceNumber}
                                        onChange={e => setReferenceNumber(e.target.value)}
                                        placeholder="e.g. CHQ-9912 or Bank Ref"
                                    />
                                </>
                            )}
                            {isAllocateMode && (
                                <div className="sm:col-span-2 flex flex-col justify-center">
                                    <span className="text-xs font-semibold text-slate-500 uppercase">Available Advance Credit</span>
                                    <div className="text-xl font-bold text-emerald-700">
                                        <MoneyDisplay value={initialPayment?.unapplied_amount || '0'} currency={cur} />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Open Invoices Allocation Section */}
                        <div className="space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                        {isCustomer ? 'Apply to Open Customer Invoices' : 'Apply to Open Owner Bills'}
                                    </h4>
                                    <p className="text-xs text-slate-500">
                                        Select outstanding invoices to settle, or leave unallocated to hold as an Advance Credit.
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <label className="inline-flex items-center gap-2 cursor-pointer rounded-lg bg-blue-50/70 border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-900 hover:bg-blue-100/60 transition">
                                        <input
                                            type="checkbox"
                                            checked={showCrossAgreement}
                                            onChange={e => {
                                                setShowCrossAgreement(e.target.checked);
                                                setAllocations({});
                                            }}
                                            className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span>Show all open documents across all agreements</span>
                                    </label>
                                    {eligibleInvoices.length > 0 && (
                                        <div className="flex gap-2">
                                            <Button type="button" variant="ghost" onClick={handleClearAllocations}>
                                                Clear
                                            </Button>
                                            <Button type="button" variant="secondary" onClick={handlePayAll}>
                                                Pay All Invoices
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {partyDocsApi.loading && showCrossAgreement ? (
                                <LoadingState />
                            ) : eligibleInvoices.length > 0 ? (
                                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase text-slate-500">
                                                <th className="px-3 py-2.5">Document #</th>
                                                {showCrossAgreement && <th className="px-3 py-2.5">Agreement</th>}
                                                {showCrossAgreement && <th className="px-3 py-2.5">Vehicle</th>}
                                                <th className="px-3 py-2.5">Date</th>
                                                <th className="px-3 py-2.5">Status</th>
                                                <th className="px-3 py-2.5 text-right">Balance Due</th>
                                                <th className="px-3 py-2.5 text-right w-44">Allocate ({cur})</th>
                                                <th className="px-3 py-2.5 text-center w-24">Quick</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {eligibleInvoices.map(inv => (
                                                <tr key={inv.id} className="hover:bg-blue-50/20">
                                                    <td className="px-3 py-2 font-medium text-slate-900">
                                                        {inv.invoice_number}
                                                    </td>
                                                    {showCrossAgreement && (
                                                        <td className="px-3 py-2 font-semibold text-blue-700">
                                                            {(inv as any).agreement_reference || '—'}
                                                        </td>
                                                    )}
                                                    {showCrossAgreement && (
                                                        <td className="px-3 py-2 text-slate-600">
                                                            {(inv as any).vehicle_registration || '—'}
                                                        </td>
                                                    )}
                                                    <td className="px-3 py-2 text-slate-600">
                                                        {formatBusinessDate(inv.invoice_date)}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <StatusBadge status={inv.status} />
                                                    </td>
                                                    <td className="px-3 py-2 text-right font-semibold text-rose-700">
                                                        <MoneyDisplay value={inv.balance_due} currency={cur} />
                                                    </td>
                                                    <td className="px-3 py-2 text-right">
                                                        <Input
                                                            type="number"
                                                            step="0.000001"
                                                            min="0"
                                                            max={inv.balance_due}
                                                            value={allocations[inv.id] || ''}
                                                            onChange={e => handleAllocationChange(inv.id, e.target.value)}
                                                            placeholder="0.00"
                                                            className="text-right py-1 px-2 text-xs"
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2 text-center">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            onClick={() => handlePayFull(inv)}
                                                            className="text-xs py-0.5 px-2 text-blue-600 hover:text-blue-800"
                                                        >
                                                            Full
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot>
                                            <tr className="bg-slate-50 font-semibold border-t border-slate-200">
                                                <td colSpan={showCrossAgreement ? 6 : 4} className="px-3 py-2 text-right text-slate-700">
                                                    Total Allocated Across Invoices:
                                                </td>
                                                <td className="px-3 py-2 text-right font-bold text-slate-900">
                                                    <MoneyDisplay value={totalAllocatedAmount} currency={cur} />
                                                </td>
                                                <td />
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500">
                                    No open invoices pending settlement. Any recorded payment will be held as an Advance Credit.
                                </div>
                            )}
                        </div>

                        {/* Payment Lines Table (Only in New Payment Mode) */}
                        {!isAllocateMode && (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                            Payment Instruments & Tender
                                        </h4>
                                        <p className="text-xs text-slate-500">
                                            Add one or more payment methods (Cash, Bank Transfer, Cheque, Card).
                                        </p>
                                    </div>
                                    {isPositiveDecimal(totalAllocatedAmount) && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={handleFillLineTotal}
                                            className="text-xs"
                                        >
                                            Set Amount to Allocated Total ({cur} {Number(totalAllocatedAmount).toFixed(2)})
                                        </Button>
                                    )}
                                </div>

                                <PaymentLineTable
                                    lines={lines}
                                    methods={paymentMethods}
                                    methodsLoading={methodsApi.loading}
                                    total={totalLinesAmount}
                                    onLineChange={handleLineChange}
                                    onMetadataChange={handleMetadataChange}
                                    onAddLine={handleAddLine}
                                    onRemoveLine={handleRemoveLine}
                                />
                            </div>
                        )}

                        {/* Notes */}
                        {!isAllocateMode && (
                            <Textarea
                                label="Notes / Memos"
                                value={notes}
                                onChange={e => setNotes(e.target.value)}
                                placeholder="Optional payment receipt notes or details..."
                                rows={2}
                            />
                        )}

                        {/* Summary / Reconciliation Footer */}
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                            <div className="flex justify-between text-xs text-slate-600">
                                <span>Total Payment Amount:</span>
                                <span className="font-bold text-slate-900">
                                    <MoneyDisplay value={totalLinesAmount} currency={cur} />
                                </span>
                            </div>
                            <div className="flex justify-between text-xs text-slate-600">
                                <span>Applied to Invoices / Bills:</span>
                                <span className="font-bold text-emerald-700">
                                    <MoneyDisplay value={totalAllocatedAmount} currency={cur} />
                                </span>
                            </div>
                            {compareDecimalStrings(totalLinesAmount, totalAllocatedAmount) > 0 && (
                                <div className="flex justify-between text-xs text-indigo-700 font-semibold pt-1 border-t border-slate-200">
                                    <span>Advance / Unapplied Surplus Credit:</span>
                                    <span>
                                        <MoneyDisplay
                                            value={String(Number(totalLinesAmount) - Number(totalAllocatedAmount))}
                                            currency={cur}
                                        />
                                    </span>
                                </div>
                            )}
                            {compareDecimalStrings(totalAllocatedAmount, totalLinesAmount) > 0 && (
                                <div className="text-xs text-rose-600 font-semibold pt-1">
                                    ⚠️ Allocated amount exceeds total payment amount! Please adjust allocation or payment amount.
                                </div>
                            )}
                        </div>

                        {/* Modal Action Buttons */}
                        <div className="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="primary"
                                loading={submitting}
                                disabled={!canSubmit}
                            >
                                {isAllocateMode
                                    ? 'Confirm Allocation'
                                    : isCustomer
                                        ? 'Record Customer Receipt'
                                        : 'Record Owner Disbursement'}
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </Modal>
    );
}
