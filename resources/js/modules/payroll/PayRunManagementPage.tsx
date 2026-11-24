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
import {
    approvePayRun,
    calculatePayRun,
    calculateRetroArrears,
    createPayPeriod,
    createPayRun,
    listPayPeriods,
    listPayRuns,
    payPayRun,
    reversePayRun,
} from './payrollApi';
import type { PayPeriod, PayRun } from './payrollTypes';

export default function PayRunManagementPage() {
    const [payRuns, setPayRuns] = useState<PayRun[]>([]);
    const [periods, setPeriods] = useState<PayPeriod[]>([]);
    const [selectedPeriodId, setSelectedPeriodId] = useState<number | ''>('');
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    // Create Pay Period Modal
    const [showPeriodModal, setShowPeriodModal] = useState(false);
    const [periodName, setPeriodName] = useState('');
    const [periodStart, setPeriodStart] = useState('');
    const [periodEnd, setPeriodEnd] = useState('');
    const [payoutDate, setPayoutDate] = useState('');
    const [workingDays, setWorkingDays] = useState(22);

    // Create Pay Run Modal
    const [showRunModal, setShowRunModal] = useState(false);
    const [runNotes, setRunNotes] = useState('');

    // Reversal Modal
    const [reversalTarget, setReversalTarget] = useState<PayRun | null>(null);
    const [reversalReason, setReversalReason] = useState('');

    // Retro Arrears Modal
    const [showRetroModal, setShowRetroModal] = useState(false);
    const [retroEmployee, setRetroEmployee] = useState<EmployeeSummary | null>(null);
    const [retroEffectiveDate, setRetroEffectiveDate] = useState('');
    const [retroTargetPeriodId, setRetroTargetPeriodId] = useState<number | ''>('');
    const [retroSuccessMsg, setRetroSuccessMsg] = useState<string | null>(null);

    const loadData = useCallback(() => {
        Promise.all([listPayPeriods(), listPayRuns()])
            .then(([pData, rData]) => {
                setPeriods(pData);
                setPayRuns(rData);
                setSelectedPeriodId((prev) => (prev === '' && pData.length > 0 ? pData[0].id : prev));
            })
            .catch((err) => setErrorMsg(err.message || 'Failed to load pay runs.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        loadData();
    }, [loadData]);

    const handleCreatePeriod = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await createPayPeriod({
                name: periodName,
                cycle_type: 'monthly',
                period_start: periodStart,
                period_end: periodEnd,
                payout_date: payoutDate,
                total_working_days: workingDays,
            });
            setShowPeriodModal(false);
            setPeriodName('');
            setPeriodStart('');
            setPeriodEnd('');
            setPayoutDate('');
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Failed to create pay period.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleCreateRun = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedPeriodId) return;
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await createPayRun({
                pay_period_id: Number(selectedPeriodId),
                notes: runNotes,
            });
            setShowRunModal(false);
            setRunNotes('');
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Failed to create pay run.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleCalculate = async (id: number) => {
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await calculatePayRun(id);
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Calculation failed.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleApprove = async (id: number) => {
        if (!confirm('Are you sure you want to approve this pay run? Figures will become immutable.')) return;
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await approvePayRun(id);
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Approval failed.');
        } finally {
            setActionLoading(false);
        }
    };

    const handlePay = async (id: number) => {
        if (!confirm('Proceed to payout and post journal entries to Finance General Ledger?')) return;
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await payPayRun(id);
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Payment posting failed.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleReverse = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!reversalTarget || !reversalReason) return;
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await reversePayRun(reversalTarget.id, reversalReason);
            setReversalTarget(null);
            setReversalReason('');
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Reversal failed.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleCalculateRetro = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!retroEmployee || !retroEffectiveDate || !retroTargetPeriodId) return;
        setActionLoading(true);
        setErrorMsg(null);
        setRetroSuccessMsg(null);
        try {
            await calculateRetroArrears({
                employee_id: retroEmployee.id,
                effective_from_date: retroEffectiveDate,
                target_pay_period_id: Number(retroTargetPeriodId),
            });
            setRetroSuccessMsg('Retroactive arrears calculated and queued as adjustment successfully.');
            setTimeout(() => {
                setShowRetroModal(false);
                setRetroSuccessMsg(null);
                setRetroEmployee(null);
                setRetroEffectiveDate('');
            }, 1800);
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Retro calculation failed.');
        } finally {
            setActionLoading(false);
        }
    };

    const filteredRuns = selectedPeriodId
        ? payRuns.filter((r) => r.pay_period_id === Number(selectedPeriodId))
        : payRuns;

    // Aggregate summary for selected period
    const totalGross = filteredRuns.reduce((sum, r) => sum + Number(r.total_gross_pay || 0), 0);
    const totalNet = filteredRuns.reduce((sum, r) => sum + Number(r.total_net_pay || 0), 0);
    const totalStatutory = filteredRuns.reduce((sum, r) => sum + Number(r.total_employee_statutory || 0) + Number(r.total_employer_statutory || 0), 0);
    const totalTax = filteredRuns.reduce((sum, r) => sum + Number(r.total_tax || 0), 0);

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300';
            case 'approved':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
            case 'calculated':
                return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
            case 'reversed':
                return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    return (
        <div className="space-y-6 p-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Payroll Runs & Execution
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Process payroll batches, evaluate attendance deductions, approve pay runs, and post to General Ledger.
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setShowRetroModal(true)}
                        className="rounded-md border border-purple-300 bg-purple-50 px-3.5 py-2 text-sm font-semibold text-purple-700 shadow-sm hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-950/40 dark:text-purple-300"
                    >
                        + Retro Arrears
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowPeriodModal(true)}
                        className="rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        + New Pay Period
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowRunModal(true)}
                        disabled={periods.length === 0}
                        className="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                    >
                        + Start Pay Run
                    </button>
                </div>
            </div>

            {errorMsg && (
                <div className="rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                    {errorMsg}
                </div>
            )}

            {/* KPI Cards */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Total Gross Pay</span>
                    <p className="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${totalGross.toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Net Take-Home Pay</span>
                    <p className="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">${totalNet.toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Total Statutory (EE + ER)</span>
                    <p className="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${totalStatutory.toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Total PAYE Tax</span>
                    <p className="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${totalTax.toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
                </div>
            </div>

            {/* Period Filter & Pay Runs Table */}
            <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                    <div className="flex items-center gap-3">
                        <label className="text-xs font-medium text-gray-600 dark:text-gray-400">Filter Period:</label>
                        <select
                            value={selectedPeriodId}
                            onChange={(e) => setSelectedPeriodId(e.target.value === '' ? '' : Number(e.target.value))}
                            className="rounded border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">All Periods</option>
                            {periods.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.name} ({p.period_start} to {p.period_end})
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {loading ? (
                    <div className="py-12 text-center text-sm text-gray-500">Loading pay runs...</div>
                ) : filteredRuns.length === 0 ? (
                    <div className="py-12 text-center text-sm text-gray-500">No pay runs found. Click "+ Start Pay Run" to begin.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                <tr>
                                    <th className="px-4 py-3">Pay Run #</th>
                                    <th className="px-4 py-3">Period</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Employees</th>
                                    <th className="px-4 py-3">Gross Pay</th>
                                    <th className="px-4 py-3">Net Pay</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {filteredRuns.map((run) => (
                                    <tr key={run.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td className="px-4 py-3 font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                            {run.pay_run_number}
                                        </td>
                                        <td className="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {run.period?.name || 'N/A'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`rounded px-2 py-0.5 text-xs font-medium uppercase ${getStatusBadge(run.status)}`}>
                                                {run.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {run.total_employees}
                                        </td>
                                        <td className="px-4 py-3 text-xs font-medium text-gray-900 dark:text-gray-100">
                                            ${Number(run.total_gross_pay).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-4 py-3 text-xs font-bold text-gray-900 dark:text-gray-100">
                                            ${Number(run.total_net_pay).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {run.status === 'draft' && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleCalculate(run.id)}
                                                        disabled={actionLoading}
                                                        className="rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300"
                                                    >
                                                        Calculate
                                                    </button>
                                                )}
                                                {run.status === 'calculated' && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCalculate(run.id)}
                                                            disabled={actionLoading}
                                                            className="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
                                                        >
                                                            Recalculate
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleApprove(run.id)}
                                                            disabled={actionLoading}
                                                            className="rounded bg-blue-600 px-2 py-1 text-xs font-medium text-white hover:bg-blue-500"
                                                        >
                                                            Approve
                                                        </button>
                                                    </>
                                                )}
                                                {run.status === 'approved' && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handlePay(run.id)}
                                                        disabled={actionLoading}
                                                        className="rounded bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500"
                                                    >
                                                        Pay & GL Post
                                                    </button>
                                                )}
                                                {(run.status === 'approved' || run.status === 'paid') && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setReversalTarget(run)}
                                                        className="rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100 dark:bg-red-950 dark:text-red-300"
                                                    >
                                                        Reverse
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Create Pay Period Modal */}
            {showPeriodModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">Create Pay Period</h2>
                        <form onSubmit={handleCreatePeriod} className="mt-4 space-y-3">
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Period Name</label>
                                <input
                                    type="text"
                                    required
                                    value={periodName}
                                    onChange={(e) => setPeriodName(e.target.value)}
                                    placeholder="e.g. July 2026 Monthly"
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                    <input
                                        type="date"
                                        required
                                        value={periodStart}
                                        onChange={(e) => setPeriodStart(e.target.value)}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                    <input
                                        type="date"
                                        required
                                        value={periodEnd}
                                        onChange={(e) => setPeriodEnd(e.target.value)}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Payout Date</label>
                                    <input
                                        type="date"
                                        required
                                        value={payoutDate}
                                        onChange={(e) => setPayoutDate(e.target.value)}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Working Days</label>
                                    <input
                                        type="number"
                                        required
                                        min="1"
                                        max="31"
                                        value={workingDays}
                                        onChange={(e) => setWorkingDays(Number(e.target.value))}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setShowPeriodModal(false)}
                                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading}
                                    className="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    Save Period
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Create Pay Run Modal */}
            {showRunModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">Start New Pay Run</h2>
                        <form onSubmit={handleCreateRun} className="mt-4 space-y-3">
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Target Period</label>
                                <select
                                    value={selectedPeriodId}
                                    onChange={(e) => setSelectedPeriodId(Number(e.target.value))}
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                >
                                    {periods.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({p.period_start} to {p.period_end})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Notes / Scope</label>
                                <textarea
                                    value={runNotes}
                                    onChange={(e) => setRunNotes(e.target.value)}
                                    placeholder="e.g. Regular monthly pay run with overtime adjustments"
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    rows={2}
                                />
                            </div>
                            <div className="flex justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setShowRunModal(false)}
                                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading}
                                    className="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    Create Draft Run
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Reversal Confirmation Modal */}
            {reversalTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-red-600 dark:text-red-400">Reverse Pay Run: {reversalTarget.pay_run_number}</h2>
                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Reversing will void all payslips, reverse the General Ledger journal entry, and reset applied adjustments.
                        </p>
                        <form onSubmit={handleReverse} className="mt-4 space-y-3">
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Audit Reversal Reason</label>
                                <textarea
                                    required
                                    value={reversalReason}
                                    onChange={(e) => setReversalReason(e.target.value)}
                                    placeholder="State why this pay run is being reversed..."
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    rows={3}
                                />
                            </div>
                            <div className="flex justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setReversalTarget(null)}
                                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading}
                                    className="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                                >
                                    Confirm Reversal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Retroactive Arrears Calculation Modal */}
            {showRetroModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white">Calculate Retroactive Arrears</h2>
                            <button onClick={() => setShowRetroModal(false)} className="text-gray-400 hover:text-gray-600">&times;</button>
                        </div>

                        {retroSuccessMsg && (
                            <div className="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/40 dark:text-green-300">
                                {retroSuccessMsg}
                            </div>
                        )}

                        <form onSubmit={handleCalculateRetro} className="mt-4 space-y-4">
                            <div>
                                <EmployeeLookupSelect
                                    label="Employee"
                                    value={retroEmployee}
                                    onChange={setRetroEmployee}
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Backdated Effective Date</label>
                                <input
                                    type="date"
                                    required
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    value={retroEffectiveDate}
                                    onChange={(e) => setRetroEffectiveDate(e.target.value)}
                                />
                                <p className="mt-1 text-xs text-gray-500">Historical date from which the new salary level is effective.</p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Target Pay Period (For Payout)</label>
                                <select
                                    required
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    value={retroTargetPeriodId}
                                    onChange={(e) => setRetroTargetPeriodId(e.target.value ? Number(e.target.value) : '')}
                                >
                                    <option value="">-- Select Target Period --</option>
                                    {periods.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({p.period_start} to {p.period_end})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex justify-end gap-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setShowRetroModal(false)}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading || !retroEmployee || !retroEffectiveDate || !retroTargetPeriodId}
                                    className="rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 disabled:opacity-50"
                                >
                                    {actionLoading ? 'Calculating...' : 'Compute Arrears'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
