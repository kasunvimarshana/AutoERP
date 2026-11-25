/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { EmployeeLookupSelect } from './components/EmployeeLookupSelect';
import { HrLeaveTypeSelect } from './components/HrLeaveTypeSelect';
import {
    actionLeaveRequest,
    applyLeave,
    cancelLeaveRequest,
    claimCompOff,
    listLeaveBalances,
    listLeaveRequests,
} from './hrApi';
import { hrPermissions } from './hrPermissions';
import type { EmployeeSummary, HrLeaveBalance, HrLeaveRequest, HrLeaveType } from './hrTypes';

export default function LeaveManagementPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.leavesView);
    const canApply = hasPermission(auth, hrPermissions.leavesApply);
    const canManage = hasPermission(auth, hrPermissions.leavesManage);

    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [balances, setBalances] = useState<HrLeaveBalance[]>([]);
    const [requests, setRequests] = useState<HrLeaveRequest[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Modals
    const [showApplyModal, setShowApplyModal] = useState(false);
    const [showCompOffModal, setShowCompOffModal] = useState(false);

    // Apply Form
    const [selectedLeaveType, setSelectedLeaveType] = useState<HrLeaveType | null>(null);
    const [startDate, setStartDate] = useState(() => businessDateInputValue());
    const [endDate, setEndDate] = useState(() => businessDateInputValue());
    const [durationType, setDurationType] = useState<'full_day' | 'half_day_morning' | 'half_day_afternoon' | 'custom_hours'>('full_day');
    const [totalDays, setTotalDays] = useState('1.00');
    const [reason, setReason] = useState('');

    // Comp-off Form
    const [workedDate, setWorkedDate] = useState(() => businessDateInputValue());
    const [compOffDays, setCompOffDays] = useState('1.00');
    const [compOffReason, setCompOffReason] = useState('');

    const fetchData = useCallback(() => {
        setLoading(true);
        setError(null);

        Promise.all([
            listLeaveBalances({ employee_id: selectedEmployee?.id }),
            listLeaveRequests({ employee_id: selectedEmployee?.id }),
        ])
            .then(([balRes, reqRes]) => {
                setBalances(balRes);
                setRequests(reqRes.data);
            })
            .catch((err) => setError(err?.response?.data?.message || 'Failed to load leave data'))
            .finally(() => setLoading(false));
    }, [selectedEmployee?.id]);

    useEffect(() => {
        if (canView) {
            queueMicrotask(() => {
                fetchData();
            });
        }
    }, [canView, fetchData]);

    if (!canView) return <AccessDeniedPage />;

    const handleApplySubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !selectedLeaveType) return;
        try {
            await applyLeave({
                employee_id: selectedEmployee.id,
                leave_type_id: selectedLeaveType.id,
                start_date: startDate,
                end_date: endDate,
                total_days: totalDays,
                duration_type: durationType,
                reason,
            });
            setShowApplyModal(false);
            fetchData();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Leave application failed');
        }
    };

    const handleAction = async (id: number, approve: boolean) => {
        try {
            await actionLeaveRequest(id, approve);
            fetchData();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to action leave request');
        }
    };

    const handleCancel = async (id: number) => {
        const cancelReason = window.prompt('Please enter cancellation reason:');
        if (!cancelReason) return;
        try {
            await cancelLeaveRequest(id, cancelReason);
            fetchData();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Cancellation failed');
        }
    };

    const handleCompOffSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee) return;
        try {
            await claimCompOff({
                employee_id: selectedEmployee.id,
                worked_date: workedDate,
                claimed_days: compOffDays,
                reason: compOffReason,
            });
            setShowCompOffModal(false);
            fetchData();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Comp-off claim failed');
        }
    };

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Leave & Time-Off Management"
                description="Manage leave balances, applications, approvals, and compensatory off claims."
                actions={
                    <div className="flex gap-2">
                        {canApply && (
                            <>
                                <Button variant="secondary" onClick={() => setShowCompOffModal(true)}>
                                    Claim Comp-Off
                                </Button>
                                <Button variant="primary" onClick={() => setShowApplyModal(true)}>
                                    + Apply for Leave
                                </Button>
                            </>
                        )}
                    </div>
                }
            />

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            {/* Employee Filter */}
            <div className="flex items-center gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div className="w-80">
                    <EmployeeLookupSelect
                        label="Filter by Employee"
                        value={selectedEmployee}
                        onChange={setSelectedEmployee}
                    />
                </div>
                {selectedEmployee && (
                    <Button variant="secondary" onClick={() => setSelectedEmployee(null)}>
                        Clear Filter
                    </Button>
                )}
            </div>

            {/* Balance Cards Grid */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {balances.length === 0 ? (
                    <div className="col-span-full rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        No leave balances initialized for this period.
                    </div>
                ) : (
                    balances.map((b) => (
                        <div
                            key={b.id}
                            className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-semibold text-gray-800 dark:text-gray-100">{b.leave_type?.name}</span>
                                <span
                                    className="h-3 w-3 rounded-full"
                                    style={{ backgroundColor: b.leave_type?.color || '#10B981' }}
                                />
                            </div>
                            <div className="mt-4 flex items-baseline justify-between">
                                <div className="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                                    {b.current_balance} <span className="text-sm font-normal text-gray-500">days</span>
                                </div>
                                <div className="text-xs text-gray-400">Quota: {b.accrued_days}</div>
                            </div>
                            <div className="mt-3 flex justify-between border-t border-gray-100 pt-2 text-xs text-gray-500 dark:border-gray-700">
                                <span>Used: {b.used_days}</span>
                                <span>Pending: {b.pending_approval_days}</span>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Requests Table */}
            <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">Leave Applications Queue</h3>
                </div>
                {loading ? (
                    <LoadingState label="Loading requests..." />
                ) : (
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Employee</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Leave Type</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Duration</th>
                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Days</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Reason</th>
                                <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Status</th>
                                <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {requests.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="py-8 text-center text-sm text-gray-500">
                                        No leave requests submitted.
                                    </td>
                                </tr>
                            ) : (
                                requests.map((req) => (
                                    <tr key={req.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {req.employee?.display_name}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            <span
                                                className="inline-block rounded px-2 py-0.5 text-xs font-medium text-white"
                                                style={{ backgroundColor: req.leave_type?.color || '#10B981' }}
                                            >
                                                {req.leave_type?.name}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {req.start_date === req.end_date ? req.start_date : `${req.start_date} to ${req.end_date}`}
                                            {req.duration_type !== 'full_day' && (
                                                <div className="text-xs text-gray-400">{req.duration_type}</div>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {req.total_days}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {req.reason}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center">
                                            <StatusBadge status={req.status} />
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                            <div className="flex justify-center gap-2">
                                                {canManage && req.status === 'pending' && (
                                                    <>
                                                        <Button size="sm" variant="primary" onClick={() => handleAction(req.id, true)}>
                                                            Approve
                                                        </Button>
                                                        <Button size="sm" variant="danger" onClick={() => handleAction(req.id, false)}>
                                                            Reject
                                                        </Button>
                                                    </>
                                                )}
                                                {canApply && (req.status === 'pending' || req.status === 'approved') && (
                                                    <Button size="sm" variant="secondary" onClick={() => handleCancel(req.id)}>
                                                        Cancel
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                )}
            </div>

            {/* Modal: Apply for Leave */}
            {showApplyModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Apply for Leave</h3>
                        <form onSubmit={handleApplySubmit} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Employee"
                                value={selectedEmployee}
                                onChange={setSelectedEmployee}
                            />
                            <HrLeaveTypeSelect
                                label="Leave Type"
                                value={selectedLeaveType}
                                onChange={setSelectedLeaveType}
                            />
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Duration Type</label>
                                    <select
                                        className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={durationType}
                                        onChange={(e) => {
                                            const val = e.target.value as typeof durationType;
                                            setDurationType(val);
                                            if (val.startsWith('half_day')) {
                                                setTotalDays('0.50');
                                            }
                                        }}
                                    >
                                        <option value="full_day">Full Day</option>
                                        <option value="half_day_morning">Half Day (Morning)</option>
                                        <option value="half_day_afternoon">Half Day (Afternoon)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Total Days</label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={totalDays}
                                        onChange={(e) => setTotalDays(e.target.value)}
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    rows={2}
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Reason for taking leave..."
                                    required
                                />
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowApplyModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!selectedEmployee || !selectedLeaveType || !reason}>
                                    Submit Application
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Claim Comp-Off */}
            {showCompOffModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Claim Compensatory Off</h3>
                        <form onSubmit={handleCompOffSubmit} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Employee"
                                value={selectedEmployee}
                                onChange={setSelectedEmployee}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Date Worked (Holiday / Rest Day)</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={workedDate}
                                    onChange={(e) => setWorkedDate(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Claimed Days</label>
                                <input
                                    type="number"
                                    step="0.5"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={compOffDays}
                                    onChange={(e) => setCompOffDays(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Reason / Duties Performed</label>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    rows={2}
                                    value={compOffReason}
                                    onChange={(e) => setCompOffReason(e.target.value)}
                                    placeholder="Duties performed on rest day or holiday..."
                                    required
                                />
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowCompOffModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!selectedEmployee || !compOffReason}>
                                    Submit Claim
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
