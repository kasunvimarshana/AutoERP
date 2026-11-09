import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import {
    AgreementKind,
    AgreementStatus,
    type Agreement,
} from './agreements';
import {
    getAgreementFinancialSummary,
    createAgreementFinancialAdjustment,
    type AgreementFinancialSummary,
} from './agreementApi';

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
    const today = new Date().toISOString().split('T')[0];
    const [adjustmentType, setAdjustmentType] = useState<'credit_note' | 'debit_note'>('credit_note');
    const [amount, setAmount] = useState('');
    const [reason, setReason] = useState('');
    const [invoiceDate, setInvoiceDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [exchangeRate, setExchangeRate] = useState('1.000000');

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
            });

            setFeedback(
                `Successfully issued ${adjustmentType === 'credit_note' ? 'Credit Note' : 'Debit Note'} ${result.invoice_number}.`,
            );
            setShowAdjustmentDialog(false);
            setAmount('');
            setReason('');
            setDueDate('');
            reloadSummary();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSubmitting(false);
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
                        {canManageAdjustments && agreement.status === AgreementStatus.Active && (
                            <Button
                                variant="primary"
                                onClick={() => setShowAdjustmentDialog(true)}
                            >
                                + Issue Credit / Debit Note
                            </Button>
                        )}
                    </div>

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

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {summary.documents.map(doc => (
                                    <tr key={doc.id} className="transition-colors hover:bg-blue-50/30">
                                        <td className="px-4 py-3 font-medium text-slate-900">
                                            {doc.invoice_number}
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
                                    </tr>
                                ))}
                                {summary.documents.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-6 text-center text-slate-500">
                                            No financial invoices or adjustment notes issued yet for this agreement.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
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
                </>
            )}
        </div>
    );
}
