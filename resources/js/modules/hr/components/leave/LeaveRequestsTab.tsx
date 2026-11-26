/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginationMeta } from '@/shared/types/pagination';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { HrLeaveTypeSelect } from '../HrLeaveTypeSelect';
import { extractHrApiError } from '../roster/rosterError';
import {
    actionLeaveRequest,
    applyLeave,
    cancelLeaveRequest,
    listLeaveBalances,
    listLeaveRequests,
} from '../../hrApi';
import {
    getLeaveDurationTypeLabel,
    LEAVE_DURATION_TYPE_OPTIONS,
    LEAVE_REQUEST_STATUS_OPTIONS,
} from '../../hrConstants';
import type {
    EmployeeSummary,
    HrLeaveBalance,
    HrLeaveRequest,
    HrLeaveType,
    LeaveDurationType,
} from '../../hrTypes';

interface LeaveRequestsTabProps {
    canApply: boolean;
    canManage: boolean;
}

export function LeaveRequestsTab({ canApply, canManage }: LeaveRequestsTabProps) {
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [statusFilter, setStatusFilter] = useState('');
    const [calendarYear, setCalendarYear] = useState(() => Number(businessDateInputValue().slice(0, 4)));
    const [balances, setBalances] = useState<HrLeaveBalance[]>([]);
    const [requests, setRequests] = useState<HrLeaveRequest[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Apply Leave Modal
    const [showApplyModal, setShowApplyModal] = useState(false);
    const [applyEmployee, setApplyEmployee] = useState<EmployeeSummary | null>(null);
    const [selectedLeaveType, setSelectedLeaveType] = useState<HrLeaveType | null>(null);
    const [startDate, setStartDate] = useState(() => businessDateInputValue());
    const [endDate, setEndDate] = useState(() => businessDateInputValue());
    const [durationType, setDurationType] = useState<LeaveDurationType>('full_day');
    const [totalDays, setTotalDays] = useState('1.00');
    const [reason, setReason] = useState('');
    const [applyModalError, setApplyModalError] = useState<string | null>(null);
    const [submittingApply, setSubmittingApply] = useState(false);

    // Cancel Leave Modal
    const [cancellingRequest, setCancellingRequest] = useState<HrLeaveRequest | null>(null);
    const [cancelReason, setCancelReason] = useState('');
    const [cancelModalError, setCancelModalError] = useState<string | null>(null);
    const [submittingCancel, setSubmittingCancel] = useState(false);

    const fetchData = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);

        Promise.all([
            listLeaveBalances({
                employee_id: selectedEmployee?.id,
                calendar_year: calendarYear,
                per_page: 50,
            }),
            listLeaveRequests({
                employee_id: selectedEmployee?.id,
                status: statusFilter || undefined,
                page: targetPage,
                per_page: 25,
            }),
        ])
            .then(([balRes, reqRes]) => {
                setBalances(balRes.data);
                setRequests(reqRes.data);
                setMeta(reqRes.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load leave data')))
            .finally(() => setLoading(false));
    }, [calendarYear, page, selectedEmployee?.id, statusFilter]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchData(page);
        });
    }, [fetchData, page]);

    const openApplyModal = () => {
        setApplyEmployee(selectedEmployee);
        setSelectedLeaveType(null);
        setStartDate(businessDateInputValue());
        setEndDate(businessDateInputValue());
        setDurationType('full_day');
        setTotalDays('1.00');
        setReason('');
        setApplyModalError(null);
        setShowApplyModal(true);
    };

    const handleApplySubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!applyEmployee || !selectedLeaveType) return;
        setSubmittingApply(true);
        setApplyModalError(null);
        try {
            await applyLeave({
                employee_id: applyEmployee.id,
                leave_type_id: selectedLeaveType.id,
                start_date: startDate,
                end_date: endDate,
                total_days: durationType === 'custom_hours' ? totalDays : undefined,
                duration_type: durationType,
                reason,
            });
            setShowApplyModal(false);
            fetchData(1);
            setPage(1);
        } catch (err) {
            setApplyModalError(extractHrApiError(err, 'Leave application failed'));
        } finally {
            setSubmittingApply(false);
        }
    };

    const handleAction = async (req: HrLeaveRequest, approve: boolean) => {
        setError(null);
        try {
            await actionLeaveRequest(req.id, approve, undefined, req.row_version);
            fetchData(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to action leave request'));
        }
    };

    const openCancelModal = (req: HrLeaveRequest) => {
        setCancellingRequest(req);
        setCancelReason('');
        setCancelModalError(null);
    };

    const handleCancelSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!cancellingRequest) return;
        setSubmittingCancel(true);
        setCancelModalError(null);
        try {
            await cancelLeaveRequest(cancellingRequest.id, cancelReason, cancellingRequest.row_version);
            setCancellingRequest(null);
            fetchData(page);
        } catch (err) {
            setCancelModalError(extractHrApiError(err, 'Cancellation failed'));
        } finally {
            setSubmittingCancel(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Filter & Action Bar */}
            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="w-72">
                        <EmployeeLookupSelect
                            label="Filter by Employee"
                            value={selectedEmployee}
                            onChange={(emp) => {
                                setSelectedEmployee(emp);
                                setPage(1);
                            }}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Calendar Year</label>
                        <input
                            type="number"
                            min={2000}
                            max={2100}
                            className="mt-1 w-28 rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={calendarYear}
                            onChange={(e) => setCalendarYear(Number(e.target.value) || new Date().getFullYear())}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select
                            className="mt-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={statusFilter}
                            onChange={(e) => {
                                setStatusFilter(e.target.value);
                                setPage(1);
                            }}
                        >
                            <option value="">All Statuses</option>
                            {LEAVE_REQUEST_STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    {(selectedEmployee || statusFilter) && (
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setSelectedEmployee(null);
                                setStatusFilter('');
                                setPage(1);
                            }}
                        >
                            Clear Filters
                        </Button>
                    )}
                </div>
                {canApply && (
                    <Button variant="primary" onClick={openApplyModal}>
                        + Apply for Leave
                    </Button>
                )}
            </div>

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            {/* Balance Cards Grid */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {balances.length === 0 ? (
                    <div className="col-span-full rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        No leave balances initialized for {calendarYear}.
                    </div>
                ) : (
                    balances.map((b) => (
                        <div
                            key={b.id}
                            className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-semibold text-gray-800 dark:text-gray-100">
                                    {b.leave_type?.name}
                                    {!selectedEmployee && b.employee?.display_name ? (
                                        <span className="block text-xs font-normal text-gray-500">{b.employee.display_name}</span>
                                    ) : null}
                                </span>
                                <span
                                    className="h-3 w-3 shrink-0 rounded-full"
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
                    <div className="overflow-x-auto">
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
                                                    <div className="text-xs text-gray-400">{getLeaveDurationTypeLabel(req.duration_type)}</div>
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
                                                            <Button size="sm" variant="primary" onClick={() => handleAction(req, true)}>
                                                                Approve
                                                            </Button>
                                                            <Button size="sm" variant="danger" onClick={() => handleAction(req, false)}>
                                                                Reject
                                                            </Button>
                                                        </>
                                                    )}
                                                    {canApply && (req.status === 'pending' || req.status === 'approved') && (
                                                        <Button size="sm" variant="secondary" onClick={() => openCancelModal(req)}>
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
                    </div>
                )}
                {meta && <Pagination meta={meta} onPageChange={setPage} />}
            </div>

            {/* Modal: Apply for Leave */}
            <Modal open={showApplyModal} onClose={() => setShowApplyModal(false)} title="Apply for Leave">
                <form onSubmit={handleApplySubmit} className="space-y-4">
                    {applyModalError && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                            {applyModalError}
                        </div>
                    )}
                    <EmployeeLookupSelect
                        label="Employee"
                        value={applyEmployee}
                        onChange={setApplyEmployee}
                    />
                    <HrLeaveTypeSelect
                        label="Leave Type"
                        value={selectedLeaveType}
                        onChange={setSelectedLeaveType}
                    />
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                required
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration Type</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={durationType}
                                onChange={(e) => {
                                    const val = e.target.value as LeaveDurationType;
                                    setDurationType(val);
                                    if (val.startsWith('half_day')) {
                                        setTotalDays('0.50');
                                    } else if (val === 'full_day') {
                                        setTotalDays('1.00');
                                    }
                                }}
                            >
                                {LEAVE_DURATION_TYPE_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {durationType === 'custom_hours' ? (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Day Fraction</label>
                                <input
                                    type="number"
                                    step="0.25"
                                    min="0.25"
                                    max="1.00"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={totalDays}
                                    onChange={(e) => setTotalDays(e.target.value)}
                                    required
                                />
                            </div>
                        ) : (
                            <div className="flex items-end pb-2 text-xs text-gray-500 dark:text-gray-400">
                                Working days are calculated automatically by the server.
                            </div>
                        )}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                        <textarea
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            rows={2}
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Reason for taking leave..."
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" onClick={() => setShowApplyModal(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            type="submit"
                            disabled={submittingApply || !applyEmployee || !selectedLeaveType || !reason.trim()}
                        >
                            {submittingApply ? 'Submitting...' : 'Submit Application'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal: Cancel Leave Request */}
            <Modal
                open={cancellingRequest !== null}
                onClose={() => setCancellingRequest(null)}
                title="Cancel Leave Request"
            >
                <form onSubmit={handleCancelSubmit} className="space-y-4">
                    {cancelModalError && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                            {cancelModalError}
                        </div>
                    )}
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        Cancel leave for <strong>{cancellingRequest?.employee?.display_name}</strong> ({cancellingRequest?.start_date} to {cancellingRequest?.end_date})?
                    </p>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Cancellation Reason</label>
                        <textarea
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            rows={2}
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            placeholder="Enter reason for cancelling..."
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" onClick={() => setCancellingRequest(null)}>
                            Close
                        </Button>
                        <Button variant="danger" type="submit" disabled={submittingCancel || !cancelReason.trim()}>
                            {submittingCancel ? 'Cancelling...' : 'Confirm Cancel'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
