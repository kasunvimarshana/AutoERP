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
import {
    assignShift,
    cancelShiftAssignment,
    listShiftAssignments,
    updateShiftAssignment,
} from '../../hrApi';
import {
    DAY_OF_WEEK_OPTIONS,
    getDayOfWeekLabel,
    SHIFT_ASSIGNMENT_TYPE_OPTIONS,
} from '../../hrConstants';
import type { EmployeeSummary, HrShift, HrShiftAssignment, ShiftAssignmentType } from '../../hrTypes';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { HrShiftSelect } from '../HrShiftSelect';
import { extractHrApiError } from './rosterError';

interface ShiftAssignmentsTabProps {
    canManage: boolean;
}

export function ShiftAssignmentsTab({ canManage }: ShiftAssignmentsTabProps) {
    const [assignments, setAssignments] = useState<HrShiftAssignment[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Filters
    const [filterEmployee, setFilterEmployee] = useState<EmployeeSummary | null>(null);
    const [filterShift, setFilterShift] = useState<HrShift | null>(null);
    const [filterCancelled, setFilterCancelled] = useState<'all' | 'active' | 'cancelled'>('active');

    // Create / Edit Modal
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<HrShiftAssignment | null>(null);
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [shift, setShift] = useState<HrShift | null>(null);
    const [assignmentType, setAssignmentType] = useState<ShiftAssignmentType>('specific_date');
    const [dayOfWeek, setDayOfWeek] = useState(1);
    const [startDate, setStartDate] = useState(() => businessDateInputValue());
    const [endDate, setEndDate] = useState(() => businessDateInputValue());
    const [isCustomTiming, setIsCustomTiming] = useState(false);
    const [customStartTime, setCustomStartTime] = useState('09:00');
    const [customEndTime, setCustomEndTime] = useState('17:00');
    const [saving, setSaving] = useState(false);

    // Cancel Modal
    const [cancellingItem, setCancellingItem] = useState<HrShiftAssignment | null>(null);
    const [cancelReason, setCancelReason] = useState('');
    const [cancelling, setCancelling] = useState(false);

    const fetchAssignments = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listShiftAssignments({
            employee_id: filterEmployee?.id,
            shift_id: filterShift?.id,
            is_cancelled: filterCancelled === 'all' ? undefined : filterCancelled === 'cancelled',
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setAssignments(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load shift assignments')))
            .finally(() => setLoading(false));
    }, [filterCancelled, filterEmployee?.id, filterShift?.id, page]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchAssignments(page);
        });
    }, [fetchAssignments, page]);

    const openCreate = () => {
        setEditing(null);
        setEmployee(null);
        setShift(null);
        setAssignmentType('specific_date');
        setDayOfWeek(1);
        setStartDate(businessDateInputValue());
        setEndDate(businessDateInputValue());
        setIsCustomTiming(false);
        setCustomStartTime('09:00');
        setCustomEndTime('17:00');
        setModalError(null);
        setShowModal(true);
    };

    const openEdit = (item: HrShiftAssignment) => {
        setEditing(item);
        setEmployee(item.employee ?? null);
        setShift(item.shift ?? null);
        setAssignmentType(item.assignment_type);
        setDayOfWeek(item.day_of_week ?? 1);
        setStartDate(item.start_date ? item.start_date.slice(0, 10) : businessDateInputValue());
        setEndDate(item.end_date ? item.end_date.slice(0, 10) : businessDateInputValue());
        setIsCustomTiming(item.is_custom_timing);
        setCustomStartTime(item.custom_start_time ? item.custom_start_time.slice(0, 5) : '09:00');
        setCustomEndTime(item.custom_end_time ? item.custom_end_time.slice(0, 5) : '17:00');
        setModalError(null);
        setShowModal(true);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!employee || !shift) return;
        setSaving(true);
        setModalError(null);
        try {
            const payload = {
                employee_id: employee.id,
                shift_id: shift.id,
                assignment_type: assignmentType,
                day_of_week: assignmentType === 'recurring' ? dayOfWeek : null,
                start_date: assignmentType === 'recurring' ? null : startDate,
                end_date: assignmentType === 'date_range' ? endDate : null,
                is_custom_timing: isCustomTiming,
                custom_start_time: isCustomTiming ? customStartTime : null,
                custom_end_time: isCustomTiming ? customEndTime : null,
            };
            if (editing) {
                await updateShiftAssignment(editing.id, {
                    ...payload,
                    expected_version: editing.row_version,
                });
            } else {
                await assignShift(payload);
            }
            setShowModal(false);
            fetchAssignments(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save shift assignment'));
        } finally {
            setSaving(false);
        }
    };

    const handleCancelConfirm = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!cancellingItem) return;
        setCancelling(true);
        setModalError(null);
        try {
            await cancelShiftAssignment(cancellingItem.id, cancelReason, cancellingItem.row_version);
            setCancellingItem(null);
            setCancelReason('');
            fetchAssignments(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to cancel shift assignment'));
        } finally {
            setCancelling(false);
        }
    };

    return (
        <div className="space-y-4">
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                    <EmployeeLookupSelect
                        label="Filter by Employee"
                        value={filterEmployee}
                        onChange={(val) => {
                            setFilterEmployee(val);
                            setPage(1);
                        }}
                    />
                    <HrShiftSelect
                        label="Filter by Shift"
                        value={filterShift}
                        onChange={(val) => {
                            setFilterShift(val);
                            setPage(1);
                        }}
                    />
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700">Status</label>
                        <select
                            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                            value={filterCancelled}
                            onChange={(e) => {
                                setFilterCancelled(e.target.value as typeof filterCancelled);
                                setPage(1);
                            }}
                        >
                            <option value="active">Active Only</option>
                            <option value="cancelled">Cancelled Only</option>
                            <option value="all">All Assignments</option>
                        </select>
                    </div>
                </div>
                {canManage && (
                    <Button variant="primary" size="sm" onClick={openCreate}>
                        + New Assignment
                    </Button>
                )}
            </div>

            {loading ? (
                <LoadingState label="Loading shift assignments..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Employee</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Shift</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Type</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Schedule</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Status</th>
                                {canManage && <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {assignments.length === 0 ? (
                                <tr>
                                    <td colSpan={canManage ? 6 : 5} className="py-8 text-center text-slate-500">No shift assignments found.</td>
                                </tr>
                            ) : (
                                assignments.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{item.employee?.display_name ?? '-'}</div>
                                            <div className="text-xs text-slate-400">{item.employee?.employee_number ?? ''}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{item.shift?.name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">
                                                {item.shift?.code ?? ''}
                                                {item.is_custom_timing && item.custom_start_time && item.custom_end_time
                                                    ? ` • Custom (${item.custom_start_time.slice(0, 5)} - ${item.custom_end_time.slice(0, 5)})`
                                                    : ''}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 capitalize text-slate-700">
                                            {item.assignment_type.replace('_', ' ')}
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {item.assignment_type === 'recurring'
                                                ? `Every ${getDayOfWeekLabel(item.day_of_week)}`
                                                : item.assignment_type === 'date_range'
                                                    ? `${item.start_date?.slice(0, 10) ?? '-'} to ${item.end_date?.slice(0, 10) ?? '-'}`
                                                    : item.start_date?.slice(0, 10) ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={item.is_cancelled ? 'cancelled' : 'active'} />
                                            {item.is_cancelled && item.cancellation_reason && (
                                                <div className="mt-0.5 text-xs text-slate-400">{item.cancellation_reason}</div>
                                            )}
                                        </td>
                                        {canManage && (
                                            <td className="px-4 py-3 text-right">
                                                {!item.is_cancelled && (
                                                    <div className="flex justify-end gap-2">
                                                        <Button variant="secondary" size="sm" onClick={() => openEdit(item)}>Edit</Button>
                                                        <Button
                                                            variant="danger"
                                                            size="sm"
                                                            onClick={() => {
                                                                setModalError(null);
                                                                setCancellingItem(item);
                                                                setCancelReason('');
                                                            }}
                                                        >
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination meta={meta} onPageChange={setPage} disabled={loading} />

            {/* Create / Edit Modal */}
            <Modal open={showModal} title={editing ? 'Edit Shift Assignment' : 'Assign Shift'} onClose={() => setShowModal(false)}>
                <form onSubmit={handleSave} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Employee" value={employee} onChange={setEmployee} />
                    <HrShiftSelect label="Shift" value={shift} onChange={setShift} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Assignment Type</label>
                        <select
                            className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                            value={assignmentType}
                            onChange={(e) => setAssignmentType(e.target.value as ShiftAssignmentType)}
                        >
                            {SHIFT_ASSIGNMENT_TYPE_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>

                    {assignmentType === 'specific_date' && (
                        <div>
                            <label className="text-sm font-medium text-slate-700">Date</label>
                            <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={startDate} onChange={(e) => setStartDate(e.target.value)} required />
                        </div>
                    )}

                    {assignmentType === 'date_range' && (
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-sm font-medium text-slate-700">Start Date</label>
                                <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={startDate} onChange={(e) => setStartDate(e.target.value)} required />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-slate-700">End Date</label>
                                <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={endDate} onChange={(e) => setEndDate(e.target.value)} required />
                            </div>
                        </div>
                    )}

                    {assignmentType === 'recurring' && (
                        <div>
                            <label className="text-sm font-medium text-slate-700">Day of Week</label>
                            <select className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" value={dayOfWeek} onChange={(e) => setDayOfWeek(Number(e.target.value))}>
                                {DAY_OF_WEEK_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" checked={isCustomTiming} onChange={(e) => setIsCustomTiming(e.target.checked)} />
                        Override shift timing for this assignment
                    </label>

                    {isCustomTiming && (
                        <div className="grid grid-cols-2 gap-3 rounded-md bg-slate-50 p-3">
                            <div>
                                <label className="text-xs font-medium text-slate-700">Custom Start Time</label>
                                <input type="time" className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm" value={customStartTime} onChange={(e) => setCustomStartTime(e.target.value)} required />
                            </div>
                            <div>
                                <label className="text-xs font-medium text-slate-700">Custom End Time</label>
                                <input type="time" className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm" value={customEndTime} onChange={(e) => setCustomEndTime(e.target.value)} required />
                            </div>
                        </div>
                    )}

                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={saving} disabled={!employee || !shift}>
                            {editing ? 'Update Assignment' : 'Save Assignment'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Cancel Assignment Modal */}
            <Modal open={cancellingItem !== null} title="Cancel Shift Assignment" onClose={() => setCancellingItem(null)}>
                <form onSubmit={handleCancelConfirm} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <p className="text-sm text-slate-600">
                        Cancel shift assignment for <span className="font-semibold text-slate-900">{cancellingItem?.employee?.display_name}</span>?
                    </p>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Cancellation Reason</label>
                        <input
                            type="text"
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            maxLength={255}
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setCancellingItem(null)}>Keep</Button>
                        <Button type="submit" variant="danger" loading={cancelling} disabled={!cancelReason.trim()}>
                            Confirm Cancel
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
