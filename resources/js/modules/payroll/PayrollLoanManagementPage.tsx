/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { EmployeeLookupSelect } from '@/modules/hr/components/EmployeeLookupSelect';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { PayPeriodSelect } from './components/PayPeriodSelect';
import {
    approveLoan,
    cancelLoan,
    createLoan,
    disburseLoan,
    getLoan,
    listLoans,
} from './payrollApi';
import type {
    LoanStatus,
    LoanType,
    PayPeriod,
    PayrollLoan,
} from './payrollTypes';

export function PayrollLoanManagementPage() {
    const [loans, setLoans] = useState<PayrollLoan[]>([]);
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [typeFilter, setTypeFilter] = useState<string>('all');

    // Dialog & Form State
    const [isApplyModalOpen, setIsApplyModalOpen] = useState(false);
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [loanType, setLoanType] = useState<LoanType>('loan');
    const [principalAmount, setPrincipalAmount] = useState<string>('50000');
    const [interestRate, setInterestRate] = useState<string>('5.00');
    const [termMonths, setTermMonths] = useState<number>(12);
    const [startPeriod, setStartPeriod] = useState<PayPeriod | null>(null);
    const [notes, setNotes] = useState<string>('');
    const [formSubmitting, setFormSubmitting] = useState(false);
    const [formError, setFormError] = useState<string | null>(null);

    // Detail & Disbursement Dialogs
    const [activeLoanDetail, setActiveLoanDetail] = useState<PayrollLoan | null>(null);
    const [isDisburseModalOpen, setIsDisburseModalOpen] = useState(false);
    const [disburseDate, setDisburseDate] = useState<string>(() => businessDateInputValue());
    const [disbursePeriod, setDisbursePeriod] = useState<PayPeriod | null>(null);

    const loadLoans = useCallback(async () => {
        try {
            const data = await listLoans({
                status: statusFilter === 'all' ? undefined : statusFilter,
                loan_type: typeFilter === 'all' ? undefined : typeFilter,
            });
            setLoans(data);
        } catch {
            setLoans([]);
        } finally {
            setLoading(false);
        }
    }, [statusFilter, typeFilter]);

    useEffect(() => {
        let isMounted = true;
        listLoans({
            status: statusFilter === 'all' ? undefined : statusFilter,
            loan_type: typeFilter === 'all' ? undefined : typeFilter,
        })
            .then((data) => {
                if (isMounted) setLoans(data);
            })
            .catch(() => {
                if (isMounted) setLoans([]);
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, [statusFilter, typeFilter]);

    // Estimated Installment calculation preview
    const calculateEstimatedEMI = () => {
        const principal = parseFloat(principalAmount) || 0;
        if (principal <= 0) return '0.00';
        if (loanType === 'advance') {
            return principal.toFixed(2);
        }
        const rate = parseFloat(interestRate) || 0;
        const months = termMonths > 0 ? termMonths : 1;
        const totalInterest = principal * (rate / 100) * (months / 12);
        const totalPayable = principal + totalInterest;
        return (totalPayable / months).toFixed(2);
    };

    const handleApplyLoan = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee) {
            setFormError('Please select an employee.');
            return;
        }

        const principal = parseFloat(principalAmount);
        if (isNaN(principal) || principal <= 0) {
            setFormError('Please enter a valid principal amount.');
            return;
        }

        setFormSubmitting(true);
        setFormError(null);

        try {
            await createLoan({
                employee_id: selectedEmployee.id,
                loan_type: loanType,
                principal_amount: principalAmount,
                annual_interest_rate: loanType === 'advance' ? '0.00' : interestRate,
                term_months: loanType === 'advance' ? 1 : termMonths,
                deduction_start_period_id: startPeriod?.id ?? null,
                notes: notes.trim() || undefined,
            });
            setIsApplyModalOpen(false);
            // Reset form
            setSelectedEmployee(null);
            setPrincipalAmount('50000');
            setNotes('');
            loadLoans();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } } };
            setFormError(error.response?.data?.message ?? 'Failed to apply for loan.');
        } finally {
            setFormSubmitting(false);
        }
    };

    const handleApprove = async (loanId: number) => {
        if (!confirm('Are you sure you want to approve this loan application?')) return;
        try {
            await approveLoan(loanId);
            loadLoans();
            if (activeLoanDetail?.id === loanId) {
                const refreshed = await getLoan(loanId);
                setActiveLoanDetail(refreshed);
            }
        } catch {
            alert('Failed to approve loan.');
        }
    };

    const handleDisburse = async () => {
        if (!activeLoanDetail) return;
        try {
            await disburseLoan(activeLoanDetail.id, {
                disbursed_date: disburseDate,
                deduction_start_period_id: disbursePeriod?.id,
            });
            setIsDisburseModalOpen(false);
            loadLoans();
            const refreshed = await getLoan(activeLoanDetail.id);
            setActiveLoanDetail(refreshed);
        } catch {
            alert('Failed to disburse loan.');
        }
    };

    const handleCancel = async (loanId: number) => {
        const reason = prompt('Please enter the cancellation reason:');
        if (!reason) return;
        try {
            await cancelLoan(loanId, reason);
            loadLoans();
            if (activeLoanDetail?.id === loanId) {
                const refreshed = await getLoan(loanId);
                setActiveLoanDetail(refreshed);
            }
        } catch {
            alert('Failed to cancel loan.');
        }
    };

    const openLoanDetail = async (loan: PayrollLoan) => {
        try {
            const fullLoan = await getLoan(loan.id);
            setActiveLoanDetail(fullLoan);
        } catch {
            setActiveLoanDetail(loan);
        }
    };

    const getStatusBadge = (status: LoanStatus) => {
        switch (status) {
            case 'draft':
                return <span className="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400">Draft</span>;
            case 'approved':
                return <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">Approved</span>;
            case 'active':
                return <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-400">Active</span>;
            case 'completed':
                return <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">Completed</span>;
            case 'cancelled':
                return <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-400">Cancelled</span>;
            default:
                return <span>{status}</span>;
        }
    };

    return (
        <div className="flex flex-col gap-6 p-6">
            {/* Header */}
            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Employee Loans & Salary Advances
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Manage loan agreements, one-off salary advances, recovery schedules, and repayments.
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <button
                        onClick={() => setIsApplyModalOpen(true)}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Apply Loan / Advance
                    </button>
                </div>
            </div>

            {/* Filters */}
            <div className="flex flex-wrap items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                <div className="flex items-center gap-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</label>
                    <select
                        className="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                    >
                        <option value="all">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Approved</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Type:</label>
                    <select
                        className="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                    >
                        <option value="all">All Types</option>
                        <option value="loan">Term Loan</option>
                        <option value="advance">Salary Advance</option>
                    </select>
                </div>
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead className="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Principal</th>
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Rate / Term</th>
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Installment</th>
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                        {loading ? (
                            <tr>
                                <td colSpan={9} className="py-8 text-center text-sm text-gray-500">Loading loan records...</td>
                            </tr>
                        ) : loans.length === 0 ? (
                            <tr>
                                <td colSpan={9} className="py-8 text-center text-sm text-gray-500">No loan or advance records found.</td>
                            </tr>
                        ) : (
                            loans.map((loan) => (
                                <tr key={loan.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                        {loan.loan_reference}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        <div className="font-medium">{loan.employee?.display_name ?? '—'}</div>
                                        <div className="text-xs text-gray-500">{loan.employee?.employee_number} · {loan.employee?.department?.name ?? 'General'}</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <span className={`inline-flex rounded px-2 py-0.5 text-xs font-medium ${
                                            loan.loan_type === 'advance' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'
                                        }`}>
                                            {loan.loan_type === 'advance' ? 'Salary Advance' : 'Term Loan'}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-mono text-gray-900 dark:text-gray-100">
                                        {Number(loan.principal_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">
                                        {loan.loan_type === 'advance' ? 'Immediate' : `${loan.annual_interest_rate}% / ${loan.term_months} mos`}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-mono font-medium text-gray-900 dark:text-gray-100">
                                        {Number(loan.monthly_installment).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        {Number(loan.balance_remaining).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        {getStatusBadge(loan.status)}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                onClick={() => openLoanDetail(loan)}
                                                className="text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                            >
                                                Details
                                            </button>
                                            {loan.status === 'draft' && (
                                                <button
                                                    onClick={() => handleApprove(loan.id)}
                                                    className="rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400"
                                                >
                                                    Approve
                                                </button>
                                            )}
                                            {loan.status === 'approved' && (
                                                <button
                                                    onClick={() => {
                                                        setActiveLoanDetail(loan);
                                                        setIsDisburseModalOpen(true);
                                                    }}
                                                    className="rounded bg-green-50 px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400"
                                                >
                                                    Disburse
                                                </button>
                                            )}
                                            {(loan.status === 'draft' || loan.status === 'approved') && (
                                                <button
                                                    onClick={() => handleCancel(loan.id)}
                                                    className="text-xs text-red-500 hover:text-red-700"
                                                >
                                                    Cancel
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Apply Loan Modal Dialog */}
            {isApplyModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white">Apply for Loan or Advance</h2>
                            <button onClick={() => setIsApplyModalOpen(false)} className="text-gray-400 hover:text-gray-600">&times;</button>
                        </div>

                        <form onSubmit={handleApplyLoan} className="mt-4 flex flex-col gap-4">
                            {formError && (
                                <div className="rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                    {formError}
                                </div>
                            )}

                            <div>
                                <EmployeeLookupSelect
                                    label="Employee"
                                    value={selectedEmployee}
                                    onChange={setSelectedEmployee}
                                />
                            </div>

                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input
                                        type="radio"
                                        checked={loanType === 'loan'}
                                        onChange={() => setLoanType('loan')}
                                    />
                                    Term Loan
                                </label>
                                <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input
                                        type="radio"
                                        checked={loanType === 'advance'}
                                        onChange={() => setLoanType('advance')}
                                    />
                                    Salary Advance
                                </label>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Principal Amount</label>
                                <input
                                    type="number"
                                    min="1"
                                    step="0.01"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    value={principalAmount}
                                    onChange={(e) => setPrincipalAmount(e.target.value)}
                                    required
                                />
                            </div>

                            {loanType === 'loan' && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Annual Interest (%)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                            value={interestRate}
                                            onChange={(e) => setInterestRate(e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Term (Months)</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="120"
                                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                            value={termMonths}
                                            onChange={(e) => setTermMonths(parseInt(e.target.value) || 1)}
                                        />
                                    </div>
                                </div>
                            )}

                            <div>
                                <PayPeriodSelect
                                    label="Deduction Start Period (Optional)"
                                    value={startPeriod}
                                    onChange={setStartPeriod}
                                />
                            </div>

                            <div className="rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300">
                                <span className="font-semibold">Estimated Monthly Installment (EMI):</span>{' '}
                                <span className="font-mono font-bold">{calculateEstimatedEMI()}</span>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Notes / Purpose</label>
                                <textarea
                                    rows={2}
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="e.g. Medical emergency advance or vehicle repair loan"
                                />
                            </div>

                            <div className="mt-4 flex justify-end gap-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setIsApplyModalOpen(false)}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={formSubmitting}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {formSubmitting ? 'Submitting...' : 'Submit Application'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Disburse Modal */}
            {isDisburseModalOpen && activeLoanDetail && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-gray-900 dark:text-white">Disburse Loan</h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Loan Reference: <span className="font-semibold">{activeLoanDetail.loan_reference}</span> ({activeLoanDetail.employee?.display_name})
                        </p>

                        <div className="mt-4 flex flex-col gap-4">
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Disbursement Date</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    value={disburseDate}
                                    onChange={(e) => setDisburseDate(e.target.value)}
                                />
                            </div>

                            <div>
                                <PayPeriodSelect
                                    label="Deduction Start Period"
                                    value={disbursePeriod}
                                    onChange={setDisbursePeriod}
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    onClick={() => setIsDisburseModalOpen(false)}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleDisburse}
                                    className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700"
                                >
                                    Confirm Disbursement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Loan Detail & Repayment History Drawer */}
            {activeLoanDetail && !isDisburseModalOpen && (
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                        <div>
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                Loan Agreement: {activeLoanDetail.loan_reference}
                            </h2>
                            <p className="text-sm text-gray-500">
                                Employee: {activeLoanDetail.employee?.display_name} ({activeLoanDetail.employee?.employee_number}) · Status: {activeLoanDetail.status}
                            </p>
                        </div>
                        <button
                            onClick={() => setActiveLoanDetail(null)}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300"
                        >
                            Close Details
                        </button>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/40">
                            <div className="text-xs text-gray-500">Principal</div>
                            <div className="text-base font-bold text-gray-900 dark:text-white">
                                {Number(activeLoanDetail.principal_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/40">
                            <div className="text-xs text-gray-500">Monthly Installment</div>
                            <div className="text-base font-bold text-gray-900 dark:text-white">
                                {Number(activeLoanDetail.monthly_installment).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/40">
                            <div className="text-xs text-gray-500">Total Paid</div>
                            <div className="text-base font-bold text-blue-600 dark:text-blue-400">
                                {Number(activeLoanDetail.total_paid).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                        </div>
                        <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/40">
                            <div className="text-xs text-gray-500">Remaining Balance</div>
                            <div className="text-base font-bold text-emerald-600 dark:text-emerald-400">
                                {Number(activeLoanDetail.balance_remaining).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                        </div>
                    </div>

                    {/* Repayment Records */}
                    <div className="mt-6">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            Repayment History ({activeLoanDetail.repayments?.length ?? 0} deductions)
                        </h3>
                        <div className="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                            <table className="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                <thead className="bg-gray-50 dark:bg-gray-800/50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Principal Repaid</th>
                                        <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Total Repaid</th>
                                        <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Balance After</th>
                                        <th className="px-3 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">Notes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                    {(activeLoanDetail.repayments?.length ?? 0) === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="py-4 text-center text-xs text-gray-500">No repayment deductions recorded yet.</td>
                                        </tr>
                                    ) : (
                                        activeLoanDetail.repayments?.map((rep) => (
                                            <tr key={rep.id}>
                                                <td className="px-3 py-2 text-xs text-gray-900 dark:text-gray-100">{rep.repayment_date}</td>
                                                <td className="px-3 py-2 text-right font-mono text-xs">{Number(rep.principal_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                                <td className="px-3 py-2 text-right font-mono text-xs font-semibold">{Number(rep.total_repaid).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                                <td className="px-3 py-2 text-right font-mono text-xs text-emerald-600">{Number(rep.balance_after).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                                <td className="px-3 py-2 text-center text-xs">
                                                    <span className={`inline-flex rounded px-2 py-0.5 text-xs font-medium ${
                                                        rep.status === 'deducted' ? 'bg-green-100 text-green-800' : rep.status === 'reversed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {rep.status}
                                                    </span>
                                                </td>
                                                <td className="px-3 py-2 text-xs text-gray-500">{rep.notes ?? '—'}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
