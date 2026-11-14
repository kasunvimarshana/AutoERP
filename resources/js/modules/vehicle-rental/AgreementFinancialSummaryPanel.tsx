import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { formatBusinessDate, businessDateInputValue } from '@/shared/utils/businessDate';
import {
    AgreementKind,
    AgreementStatus,
    type Agreement,
} from './agreements';
import {
    getAgreementFinancialSummary,
    createAgreementFinancialAdjustment,
    listBillingCycles,
    getBillingCycleExportUrl,
    reverseAgreementPayment,
    getPaymentReceiptPrintUrl,
    type AgreementFinancialSummary,
    type BillingCycle,
    type AgreementPayment,
} from './agreementApi';
import { BillingCycleWizardModal } from './BillingCycleWizardModal';
import { OwnerSettlementModal } from './OwnerSettlementModal';
import { ReassignmentHistoryPanel } from './ReassignmentHistoryPanel';
import { AgreementPaymentModal } from './AgreementPaymentModal';
import { allocateDeposit } from './depositApi';

interface Props {
    kind: AgreementKind;
    agreement: Agreement;
    canManageAdjustments: boolean;
}

export function AgreementFinancialSummaryPanel({ kind, agreement, canManageAdjustments }: Props) {
    const [summary, setSummary] = useState<AgreementFinancialSummary | null>(null);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [showAdjustmentDialog, setShowAdjustmentDialog] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [feedback, setFeedback] = useState<string | null>(null);

    // Adjustment Form state
    const [adjustmentType, setAdjustmentType] = useState<'credit_note' | 'debit_note'>('credit_note');
    const [amount, setAmount] = useState('');
    const [reason, setReason] = useState('');
    const [invoiceDate, setInvoiceDate] = useState(businessDateInputValue);
    const [dueDate, setDueDate] = useState('');
    const [exchangeRate, setExchangeRate] = useState('1.000000');
    const [targetInvoiceId, setTargetInvoiceId] = useState<number | null>(null);

    // Deposit Allocation Dialog state
    const [showDepositAllocateDialog, setShowDepositAllocateDialog] = useState(false);
    const [selectedDepositPaymentId, setSelectedDepositPaymentId] = useState<number | null>(null);
    const [selectedTargetInvoiceId, setSelectedTargetInvoiceId] = useState<number | null>(null);
    const [depositAllocateAmount, setDepositAllocateAmount] = useState('');
    const [depositSubmitting, setDepositSubmitting] = useState(false);

    // Billing Cycle state
    const [billingCycles, setBillingCycles] = useState<BillingCycle[]>([]);
    const [selectedCycle, setSelectedCycle] = useState<BillingCycle | 'new' | null>(null);
    const [showCycleModal, setShowCycleModal] = useState(false);
    const [showOwnerSettlementModal, setShowOwnerSettlementModal] = useState(false);

    // Payment Modal state
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [selectedPaymentForAllocation, setSelectedPaymentForAllocation] = useState<AgreementPayment | null>(null);
    const [initialPaymentInvoiceId, setInitialPaymentInvoiceId] = useState<number | null>(null);
    const [reversalTarget, setReversalTarget] = useState<AgreementPayment | null>(null);
    const [reversalReason, setReversalReason] = useState('');
    const [reversalSubmitting, setReversalSubmitting] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        getAgreementFinancialSummary(kind, agreement.id, controller.signal)
            .then(data => {
                if (!controller.signal.aborted) {
                    setSummary(data);
                    setError(null);
                }
            })
            .catch(failure => {
                if (!controller.signal.aborted) {
                    setError(toApiError(failure));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        listBillingCycles(agreement.id, kind)
            .then(res => {
                if (!controller.signal.aborted) {
                    setBillingCycles(res.data);
                }
            })
            .catch(() => {});

        return () => controller.abort();
    }, [kind, agreement.id, agreement.row_version, revision]);

    function reloadSummary() {
        setLoading(true);
        setRevision(r => r + 1);
    }

    async function handleIssueAdjustment(e: React.FormEvent) {
        e.preventDefault();
        if (!amount || !reason.trim()) return;

        setSubmitting(true);
        setError(null);
        setFeedback(null);

        try {
            const result = await createAgreementFinancialAdjustment(kind, agreement.id, {
                expected_version: agreement.row_version,
                adjustment_type: adjustmentType,
                amount,
                reason: reason.trim(),
                invoice_date: invoiceDate,
                due_date: dueDate || null,
                exchange_rate: exchangeRate || '1.000000',
                target_invoice_id: targetInvoiceId,
            });

            setFeedback(
                `Successfully issued ${adjustmentType === 'credit_note' ? 'Credit Note' : 'Debit Note'} ${result.invoice_number}.`,
            );
            setShowAdjustmentDialog(false);
            setAmount('');
            setReason('');
            setDueDate('');
            setTargetInvoiceId(null);
            reloadSummary();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSubmitting(false);
        }
    }

    async function handleAllocateDeposit(e: React.FormEvent) {
        e.preventDefault();
        if (!selectedDepositPaymentId || !selectedTargetInvoiceId || !depositAllocateAmount) return;

        setDepositSubmitting(true);
        setError(null);
        setFeedback(null);

        try {
            await allocateDeposit(agreement.id, {
                payment_id: selectedDepositPaymentId,
                invoice_id: selectedTargetInvoiceId,
                amount: depositAllocateAmount,
            });

            setFeedback('Successfully allocated security deposit against invoice.');
            setShowDepositAllocateDialog(false);
            setSelectedDepositPaymentId(null);
            setSelectedTargetInvoiceId(null);
            setDepositAllocateAmount('');
            reloadSummary();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setDepositSubmitting(false);
        }
    }

    if (loading && !summary) {
        return <LoadingState label="Loading agreement financial position..." />;
    }

    const cur = summary?.agreement.currency.code ?? agreement.currency.code;
    const isCustomer = kind === AgreementKind.Customer;

    return (
        <div className="space-y-6" aria-label="Agreement financial summary">
            <ErrorAlert error={error} inline />

            {feedback && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 flex items-center justify-between">
                    <span>{feedback}</span>
                    <Button variant="ghost" onClick={() => setFeedback(null)}>Dismiss</Button>
                </div>
            )}

            {summary && (
                <>
                    {/* Key Metrics Cards */}
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Invoiced</span>
                            <div className="mt-1 text-lg font-bold text-slate-900">
                                <MoneyDisplay value={summary.summary.total_invoiced} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Base + Usage + Charges</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Payments / Paid</span>
                            <div className="mt-1 text-lg font-bold text-emerald-600">
                                <MoneyDisplay value={summary.summary.total_paid} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Settled receipts</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Credit Notes</span>
                            <div className="mt-1 text-lg font-bold text-amber-600">
                                <MoneyDisplay value={summary.summary.total_credit_notes} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Allowances & rebates</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Debit Notes</span>
                            <div className="mt-1 text-lg font-bold text-indigo-600">
                                <MoneyDisplay value={summary.summary.total_debit_notes} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Recoveries / adjustments</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Balance Due</span>
                            <div className="mt-1 text-lg font-bold text-rose-600">
                                <MoneyDisplay value={summary.summary.total_remaining_balance} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Open receivables</span>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unallocated Credit</span>
                            <div className="mt-1 text-lg font-bold text-slate-700">
                                <MoneyDisplay value={summary.summary.total_unallocated_credit} currency={cur} />
                            </div>
                            <span className="text-[11px] text-slate-400">Available credit balance</span>
                        </div>
                    </div>

                    {/* Security Deposit Summary (Customer Agreements) */}
                    {summary.deposit && (
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Security Deposit Status</h4>
                                <div className="mt-1 flex items-center gap-3 text-sm">
                                    <span>Required: <span className="font-semibold">{summary.deposit.requirement ? <MoneyDisplay value={summary.deposit.requirement} currency={cur} /> : 'None'}</span></span>
                                    <span>·</span>
                                    <span>Collected: <span className="font-semibold text-emerald-700"><MoneyDisplay value={summary.deposit.net_receipts} currency={cur} /></span> ({summary.deposit.receipts_count} receipt{summary.deposit.receipts_count === 1 ? '' : 's'})</span>
                                    {summary.deposit.remaining && Number(summary.deposit.remaining) > 0 && (
                                        <>
                                            <span>·</span>
                                            <span>Remaining Requirement: <span className="font-semibold text-amber-700"><MoneyDisplay value={summary.deposit.remaining} currency={cur} /></span></span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Actions Bar */}
                    <div className="flex items-center justify-between">
                        <h3 className="text-base font-semibold text-slate-900">Linked Financial Documents</h3>
                        <div className="flex items-center gap-2">
                            {isCustomer && canManageAdjustments && agreement.status === AgreementStatus.Active && summary?.deposit && Number(summary.deposit.net_receipts) > 0 && (
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        const unappliedPayments = (summary?.deposit?.payments || []).filter(p => Number(p.unapplied_amount) > 0);
                                        const outstandingInvs = (summary?.documents || []).filter(d => d.invoice_type !== 'credit' && Number(d.balance_due) > 0);
                                        if (unappliedPayments.length > 0) {
                                            setSelectedDepositPaymentId(unappliedPayments[0].id);
                                        }
                                        if (outstandingInvs.length > 0) {
                                            setSelectedTargetInvoiceId(outstandingInvs[0].id);
                                            setDepositAllocateAmount(outstandingInvs[0].balance_due);
                                        }
                                        setShowDepositAllocateDialog(true);
                                    }}
                                >
                                    Allocate Security Deposit
                                </Button>
                            )}
                            {!isCustomer && canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                <Button
                                    variant="secondary"
                                    onClick={() => setShowOwnerSettlementModal(true)}
                                >
                                    Generate Owner Settlement Bill
                                </Button>
                            )}
                            {canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                <Button
                                    variant="primary"
                                    onClick={() => {
                                        setSelectedPaymentForAllocation(null);
                                        setInitialPaymentInvoiceId(null);
                                        setShowPaymentModal(true);
                                    }}
                                >
                                    {isCustomer ? '+ Receive Payment / Advance' : '+ Disburse Payment'}
                                </Button>
                            )}
                            {canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                <Button
                                    variant="secondary"
                                    onClick={() => setShowAdjustmentDialog(true)}
                                >
                                    + Issue Credit / Debit Note
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Security Deposit Allocation Dialog */}
                    {showDepositAllocateDialog && (
                        <form onSubmit={handleAllocateDeposit} className="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5 space-y-4 shadow-sm">
                            <div className="flex items-center justify-between border-b border-emerald-100 pb-2">
                                <h4 className="text-sm font-semibold text-slate-900">
                                    Allocate Security Deposit Against Rental Invoice
                                </h4>
                                <Button type="button" variant="ghost" onClick={() => setShowDepositAllocateDialog(false)}>
                                    Cancel
                                </Button>
                            </div>

                            <p className="text-xs text-slate-600">
                                Allocate held security deposit funds to legally reduce or settle outstanding rental invoice balances.
                            </p>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Select
                                    label="Deposit Payment Receipt"
                                    value={selectedDepositPaymentId ? String(selectedDepositPaymentId) : ''}
                                    onChange={e => setSelectedDepositPaymentId(e.target.value ? Number(e.target.value) : null)}
                                    options={[
                                        { value: '', label: 'Select Deposit Payment...' },
                                        ...((summary?.deposit?.payments || []).filter(p => Number(p.unapplied_amount) > 0).map(p => ({
                                            value: String(p.id),
                                            label: `${p.payment_number} · Available: ${cur} ${Number(p.unapplied_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
                                        }))),
                                    ]}
                                    required
                                />

                                <Select
                                    label="Target Outstanding Invoice"
                                    value={selectedTargetInvoiceId ? String(selectedTargetInvoiceId) : ''}
                                    onChange={e => {
                                        const invId = e.target.value ? Number(e.target.value) : null;
                                        setSelectedTargetInvoiceId(invId);
                                        if (invId) {
                                            const matched = (summary?.documents || []).find(d => d.id === invId);
                                            if (matched) {
                                                setDepositAllocateAmount(matched.balance_due);
                                            }
                                        }
                                    }}
                                    options={[
                                        { value: '', label: 'Select Outstanding Invoice...' },
                                        ...((summary?.documents || []).filter(d => d.invoice_type !== 'credit' && Number(d.balance_due) > 0).map(inv => ({
                                            value: String(inv.id),
                                            label: `${inv.invoice_number} (${formatBusinessDate(inv.invoice_date)}) · Balance: ${cur} ${Number(inv.balance_due).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
                                        }))),
                                    ]}
                                    required
                                />

                                <Input
                                    label={`Amount to Allocate (${cur})`}
                                    type="number"
                                    step="0.000001"
                                    min="0.000001"
                                    value={depositAllocateAmount}
                                    onChange={e => setDepositAllocateAmount(e.target.value)}
                                    placeholder="0.000000"
                                    required
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="secondary" onClick={() => setShowDepositAllocateDialog(false)} disabled={depositSubmitting}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" loading={depositSubmitting} disabled={!selectedDepositPaymentId || !selectedTargetInvoiceId || !depositAllocateAmount}>
                                    Confirm Deposit Allocation
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Adjustment Dialog */}
                    {showAdjustmentDialog && (
                        <form onSubmit={handleIssueAdjustment} className="rounded-xl border border-blue-200 bg-blue-50/40 p-5 space-y-4 shadow-sm">
                            <div className="flex items-center justify-between border-b border-blue-100 pb-2">
                                <h4 className="text-sm font-semibold text-slate-900">
                                    Issue Financial Adjustment for {agreement.reference}
                                </h4>
                                <Button type="button" variant="ghost" onClick={() => setShowAdjustmentDialog(false)}>
                                    Cancel
                                </Button>
                            </div>

                            <p className="text-xs text-slate-600">
                                Issue an explicitly authorized Credit Note (rebate/allowance reducing customer receivable) or Debit Note (additional chargeback/recovery).
                            </p>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Select
                                    label="Adjustment Type"
                                    value={adjustmentType}
                                    onChange={e => setAdjustmentType(e.target.value as 'credit_note' | 'debit_note')}
                                    options={[
                                        { value: 'credit_note', label: isCustomer ? 'Credit Note (Rebate / Allowance to Customer)' : 'Credit Note (Rebate to Owner)' },
                                        { value: 'debit_note', label: isCustomer ? 'Debit Note (Chargeback / Recovery to Customer)' : 'Debit Note (Deduction from Owner)' },
                                    ]}
                                    required
                                />

                                <Input
                                    label={`Amount (${cur})`}
                                    type="number"
                                    step="0.000001"
                                    min="0.000001"
                                    value={amount}
                                    onChange={e => setAmount(e.target.value)}
                                    placeholder="0.000000"
                                    required
                                />

                                <Input
                                    label="Invoice Date"
                                    type="date"
                                    value={invoiceDate}
                                    onChange={e => setInvoiceDate(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Select
                                    label="Target Invoice (Optional)"
                                    value={targetInvoiceId ? String(targetInvoiceId) : ''}
                                    onChange={e => setTargetInvoiceId(e.target.value ? Number(e.target.value) : null)}
                                    options={[
                                        { value: '', label: 'General / No Specific Invoice' },
                                        ...((summary?.documents || []).filter(d => d.invoice_type !== 'credit').map(inv => ({
                                            value: String(inv.id),
                                            label: `${inv.invoice_number} (${formatBusinessDate(inv.invoice_date)}) · Balance: ${cur} ${Number(inv.balance_due).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
                                        }))),
                                    ]}
                                />

                                <Input
                                    label="Due Date (Optional)"
                                    type="date"
                                    value={dueDate}
                                    onChange={e => setDueDate(e.target.value)}
                                />

                                <Input
                                    label="Exchange Rate"
                                    type="text"
                                    value={exchangeRate}
                                    onChange={e => setExchangeRate(e.target.value)}
                                    required
                                />
                            </div>

                            <Textarea
                                label="Reason / Commercial Justification"
                                value={reason}
                                onChange={e => setReason(e.target.value)}
                                placeholder="Explain reason for adjustment (e.g. breakdown downtime rebate, toll/damage chargeback)..."
                                required
                            />

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="secondary" onClick={() => setShowAdjustmentDialog(false)} disabled={submitting}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" loading={submitting}>
                                    Confirm & Issue {adjustmentType === 'credit_note' ? 'Credit Note' : 'Debit Note'}
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Documents Table */}
                    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Document #</th>
                                    <th className="px-4 py-3">Type</th>
                                    <th className="px-4 py-3">Date</th>
                                    <th className="px-4 py-3">Due Date</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Grand Total</th>
                                    <th className="px-4 py-3 text-right">Paid</th>
                                    <th className="px-4 py-3 text-right">Balance Due</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {summary.documents.map(doc => (
                                    <tr key={doc.id} className="transition-colors hover:bg-blue-50/30">
                                        <td className="px-4 py-3 font-medium text-slate-900">
                                            <Link
                                                to={`/invoices/${doc.id}`}
                                                className="font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                            >
                                                {doc.invoice_number}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${
                                                doc.invoice_type === 'credit'
                                                    ? 'bg-amber-100 text-amber-800'
                                                    : doc.invoice_type === 'debit'
                                                        ? 'bg-indigo-100 text-indigo-800'
                                                        : 'bg-blue-100 text-blue-800'
                                            }`}>
                                                {doc.invoice_type === 'credit' ? 'Credit Note' : doc.invoice_type === 'debit' ? 'Debit Note' : 'Standard Invoice'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">{formatBusinessDate(doc.invoice_date)}</td>
                                        <td className="px-4 py-3 text-slate-600">{doc.due_date ? formatBusinessDate(doc.due_date) : '—'}</td>
                                        <td className="px-4 py-3"><StatusBadge status={doc.status} /></td>
                                        <td className="px-4 py-3 text-right font-semibold text-slate-900">
                                            <MoneyDisplay value={doc.grand_total} currency={cur} />
                                        </td>
                                        <td className="px-4 py-3 text-right text-emerald-700">
                                            <MoneyDisplay value={doc.paid_total} currency={cur} />
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-rose-700">
                                            <MoneyDisplay value={doc.balance_due} currency={cur} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {doc.invoice_type !== 'credit' && Number(doc.balance_due) > 0 && canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                                <Button
                                                    variant="secondary"
                                                    onClick={() => {
                                                        setSelectedPaymentForAllocation(null);
                                                        setInitialPaymentInvoiceId(doc.id);
                                                        setShowPaymentModal(true);
                                                    }}
                                                    className="text-xs py-1 px-2.5"
                                                >
                                                    {isCustomer ? 'Receive Payment' : 'Pay Bill'}
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {summary.documents.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="px-4 py-6 text-center text-slate-500">
                                            No financial invoices or adjustment notes issued yet for this agreement.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Recorded Payments & Receipts Section */}
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">
                                    Recorded Payments & Receipts
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Audit trail of customer payment receipts, advances, owner disbursements, and receipt vouchers linked to this agreement.
                                </p>
                            </div>
                            {canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        setSelectedPaymentForAllocation(null);
                                        setInitialPaymentInvoiceId(null);
                                        setShowPaymentModal(true);
                                    }}
                                >
                                    {isCustomer ? '+ Record Payment / Advance' : '+ Record Disbursement'}
                                </Button>
                            )}
                        </div>

                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                        <th className="px-4 py-3">Payment #</th>
                                        <th className="px-4 py-3">Date</th>
                                        <th className="px-4 py-3">Type</th>
                                        <th className="px-4 py-3">Method & Reference</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Total</th>
                                        <th className="px-4 py-3 text-right">Allocated</th>
                                        <th className="px-4 py-3 text-right">Advance / Unapplied</th>
                                        <th className="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(summary.payments || []).map(p => (
                                        <tr key={p.id} className="transition-colors hover:bg-blue-50/30">
                                            <td className="px-4 py-3 font-semibold text-slate-900">
                                                <button
                                                    type="button"
                                                    onClick={() => window.open(getPaymentReceiptPrintUrl(p.id), '_blank')}
                                                    className="font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                                    title="Click to view and print official receipt"
                                                >
                                                    <span>{p.payment_number}</span>
                                                    <span className="text-[11px]">🖨️</span>
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">{formatBusinessDate(p.payment_date)}</td>
                                            <td className="px-4 py-3">
                                                <span className="capitalize font-medium text-slate-700">
                                                    {p.payment_type.replaceAll('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                <div className="space-y-0.5">
                                                    {p.methods.map((m, idx) => (
                                                        <div key={idx} className="flex items-center gap-1">
                                                            <span className="font-medium text-slate-700">{m.method_name}</span>
                                                            {m.reference && <span className="text-slate-400">({m.reference})</span>}
                                                            <span className="text-slate-500">· <MoneyDisplay value={m.amount} currency={cur} /></span>
                                                        </div>
                                                    ))}
                                                    {p.methods.length === 0 && <span className="text-slate-400 italic">—</span>}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={p.posting_status === 'posted' ? 'posted' : p.document_status} />
                                            </td>
                                            <td className="px-4 py-3 text-right font-bold text-slate-900">
                                                <MoneyDisplay value={p.total_amount} currency={cur} />
                                            </td>
                                            <td className="px-4 py-3 text-right text-emerald-700 font-medium">
                                                <MoneyDisplay value={p.allocated_amount} currency={cur} />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {Number(p.unapplied_amount) > 0 ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                        <MoneyDisplay value={p.unapplied_amount} currency={cur} />
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    {Number(p.unapplied_amount) > 0 && canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                                        <Button
                                                            variant="secondary"
                                                            onClick={() => {
                                                                setSelectedPaymentForAllocation(p);
                                                                setShowPaymentModal(true);
                                                            }}
                                                            className="text-xs py-1 px-2"
                                                        >
                                                            Allocate
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        onClick={() => window.open(getPaymentReceiptPrintUrl(p.id), '_blank')}
                                                        className="text-xs py-1 px-2"
                                                    >
                                                        Receipt
                                                    </Button>
                                                    {p.posting_status === 'posted' && p.document_status !== 'voided' && canManageAdjustments && (
                                                        <Button
                                                            variant="ghost"
                                                            onClick={() => setReversalTarget(p)}
                                                            className="text-xs py-1 px-2 text-rose-600 hover:text-rose-800"
                                                        >
                                                            Reverse
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {(summary.payments || []).length === 0 && (
                                        <tr>
                                            <td colSpan={9} className="px-4 py-6 text-center text-slate-400">
                                                No payments or disbursements recorded yet for this agreement.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Expense Allocations Section */}
                    {summary.allocations.length > 0 && (
                        <div className="space-y-3">
                            <h3 className="text-base font-semibold text-slate-900">
                                Allocated Expenses & Recoveries
                            </h3>
                            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                            <th className="px-4 py-3">Expense #</th>
                                            <th className="px-4 py-3">Type</th>
                                            <th className="px-4 py-3">Expense Date</th>
                                            <th className="px-4 py-3">Allocation</th>
                                            <th className="px-4 py-3 text-right">Amount</th>
                                            <th className="px-4 py-3">Linked Invoice</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {summary.allocations.map(alloc => (
                                            <tr key={alloc.id} className="transition-colors hover:bg-blue-50/30">
                                                <td className="px-4 py-3 font-medium text-slate-900">{alloc.expense.expense_number}</td>
                                                <td className="px-4 py-3 text-slate-600">{alloc.expense.expense_type}</td>
                                                <td className="px-4 py-3 text-slate-600">{formatBusinessDate(alloc.expense.expense_date)}</td>
                                                <td className="px-4 py-3 font-medium text-slate-700">{alloc.allocation_type_label}</td>
                                                <td className="px-4 py-3 text-right font-semibold text-slate-900">
                                                    <MoneyDisplay value={alloc.total_amount} currency={cur} />
                                                </td>
                                                <td className="px-4 py-3">
                                                    {alloc.invoice ? (
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold text-blue-700">{alloc.invoice.invoice_number}</span>
                                                            <StatusBadge status={alloc.invoice.status} />
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 italic">Not billed yet</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                    {/* Billing Cycles Section */}
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">
                                    Billing Cycles & Two-Stage Invoicing
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Manage flexible periodic billing: Stage 1 base rent invoicing, running chart attachment, ad-hoc additions/deductions, and Stage 2 finalization.
                                </p>
                            </div>
                            {canManageAdjustments && agreement.status === AgreementStatus.Active && (
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        setSelectedCycle('new');
                                        setShowCycleModal(true);
                                    }}
                                >
                                    + New Billing Cycle
                                </Button>
                            )}
                        </div>

                        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                        <th className="px-4 py-3">Cycle #</th>
                                        <th className="px-4 py-3">Period</th>
                                        <th className="px-4 py-3">Mode</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Initial Invoice</th>
                                        <th className="px-4 py-3">Final Invoice</th>
                                        <th className="px-4 py-3 text-right">Net Total</th>
                                        <th className="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {billingCycles.map(cycle => (
                                        <tr key={cycle.id} className="transition-colors hover:bg-blue-50/30">
                                            <td className="px-4 py-3 font-semibold text-slate-900">{cycle.cycle_number}</td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {formatBusinessDate(cycle.period_from)} – {formatBusinessDate(cycle.period_until)}
                                            </td>
                                            <td className="px-4 py-3 capitalize text-slate-700">{cycle.billing_mode}</td>
                                            <td className="px-4 py-3"><StatusBadge status={cycle.status} /></td>
                                            <td className="px-4 py-3">
                                                {cycle.initial_invoice ? (
                                                    <span className="font-semibold text-blue-700">{cycle.initial_invoice.invoice_number}</span>
                                                ) : (
                                                    <span className="text-slate-400 italic">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {cycle.final_invoice ? (
                                                    <span className="font-semibold text-emerald-700">{cycle.final_invoice.invoice_number}</span>
                                                ) : cycle.supplementary_invoice ? (
                                                    <span className="font-semibold text-indigo-700">{cycle.supplementary_invoice.invoice_number}</span>
                                                ) : (
                                                    <span className="text-slate-400 italic">Pending</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right font-bold text-slate-900">
                                                <MoneyDisplay value={cycle.total_billed_amount || cycle.base_rent_amount} currency={cur} />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    <Button
                                                        variant="ghost"
                                                        onClick={() => window.open(getBillingCycleExportUrl(cycle.id, 'pdf'), '_blank')}
                                                    >
                                                        Statement
                                                    </Button>
                                                    <Button
                                                        variant="secondary"
                                                        onClick={() => {
                                                            setSelectedCycle(cycle);
                                                            setShowCycleModal(true);
                                                        }}
                                                    >
                                                        Manage
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {billingCycles.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-6 text-center text-slate-400">
                                                No billing cycles created yet for this agreement.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Reassignments History (Customer Agreements) */}
                    {isCustomer && (
                        <div className="space-y-3">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">
                                    Vehicle Replacements & Driver Reassignments
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Timeline of fleet substitutions, cutover timestamps, driver handovers, and lessor pro-rata financial adjustments.
                                </p>
                            </div>
                            <ReassignmentHistoryPanel customerAgreementId={agreement.id} currencyCode={cur} />
                        </div>
                    )}
                </>
            )}

            {showCycleModal && (
                <BillingCycleWizardModal
                    open={showCycleModal}
                    onClose={() => {
                        setShowCycleModal(false);
                        setSelectedCycle(null);
                    }}
                    kind={kind}
                    agreement={agreement}
                    cycle={selectedCycle === 'new' ? null : selectedCycle}
                    onCycleUpdated={() => reloadSummary()}
                />
            )}

            {showOwnerSettlementModal && (
                <OwnerSettlementModal
                    open={showOwnerSettlementModal}
                    onClose={() => setShowOwnerSettlementModal(false)}
                    ownerAgreementId={agreement.id}
                    ownerAgreementReference={agreement.reference}
                    defaultStartDate={agreement.starts_on ?? undefined}
                    defaultEndDate={agreement.ends_on ?? undefined}
                    onBillGenerated={() => {
                        setShowOwnerSettlementModal(false);
                        reloadSummary();
                    }}
                />
            )}

            {showPaymentModal && summary && (
                <AgreementPaymentModal
                    open={showPaymentModal}
                    onClose={() => {
                        setShowPaymentModal(false);
                        setSelectedPaymentForAllocation(null);
                        setInitialPaymentInvoiceId(null);
                    }}
                    kind={kind}
                    agreement={agreement}
                    openInvoices={summary.documents}
                    initialInvoiceId={initialPaymentInvoiceId}
                    initialPayment={selectedPaymentForAllocation}
                    onPaymentSuccess={() => {
                        reloadSummary();
                        setFeedback(isCustomer ? 'Payment receipt recorded successfully.' : 'Payment disbursement recorded successfully.');
                    }}
                />
            )}

            {reversalTarget && (
                <Modal
                    open={Boolean(reversalTarget)}
                    title={`Reverse Payment · ${reversalTarget.payment_number}`}
                    onClose={() => setReversalTarget(null)}
                    closeDisabled={reversalSubmitting}
                >
                    <form
                        onSubmit={async e => {
                            e.preventDefault();
                            if (!reversalReason.trim() || reversalSubmitting) return;
                            setReversalSubmitting(true);
                            try {
                                await reverseAgreementPayment(kind, agreement.id, reversalTarget.id, {
                                    reason: reversalReason.trim(),
                                });
                                setFeedback(`Payment ${reversalTarget.payment_number} reversed successfully.`);
                                setReversalTarget(null);
                                setReversalReason('');
                                reloadSummary();
                            } catch (err) {
                                setError(toApiError(err));
                            } finally {
                                setReversalSubmitting(false);
                            }
                        }}
                        className="space-y-4 p-1"
                    >
                        <p className="text-sm text-slate-600">
                            Reversing this payment will restore the open balances on all allocated invoices, record a financial reversal voucher in the GL, and mark the receipt as reversed.
                        </p>
                        <Textarea
                            label="Reason for Reversal"
                            value={reversalReason}
                            onChange={e => setReversalReason(e.target.value)}
                            placeholder="Specify the reason for reversing this payment..."
                            rows={3}
                            required
                        />
                        <div className="flex justify-end gap-2 pt-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setReversalTarget(null)}
                                disabled={reversalSubmitting}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="danger"
                                loading={reversalSubmitting}
                                disabled={!reversalReason.trim()}
                            >
                                Confirm Reversal
                            </Button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}
