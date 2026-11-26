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
import { businessDateInputValue, shiftBusinessDate } from '@/shared/utils/businessDate';
import {
    DAY_OF_WEEK_OPTIONS,
    SHIFT_ASSIGNMENT_TYPE_OPTIONS,
    SHIFT_REALLOCATION_REASON_OPTIONS,
} from '../../hrConstants';
import { assignShift, getRoster, reallocateShiftCover, requestShiftSwap } from '../../hrApi';
import type { EmployeeSummary, HrShift, ShiftAssignmentType, ShiftReallocationReason } from '../../hrTypes';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { HrShiftSelect } from '../HrShiftSelect';
import { extractHrApiError } from './rosterError';

function currentBusinessWeekDate(offsetFromSunday: number): string {
    const today = businessDateInputValue();
    const [year, month, day] = today.split('-').map(Number);
    const dayOfWeek = new Date(Date.UTC(year, month - 1, day)).getUTCDay();
    return shiftBusinessDate(today, offsetFromSunday - dayOfWeek);
}

interface RosterGridTabProps {
    canManage: boolean;
}

export function RosterGridTab({ canManage }: RosterGridTabProps) {
    const [startDate, setStartDate] = useState(() => currentBusinessWeekDate(1));
    const [endDate, setEndDate] = useState(() => currentBusinessWeekDate(7));
    const [rosterData, setRosterData] = useState<Array<{
        employee: { id: number; employee_number: string; display_name: string };
        days: Record<string, {
            shift: HrShift | null;
            is_working: boolean;
            is_rest_day: boolean;
            is_holiday: boolean;
            holiday_name?: string | null;
            source: string;
        }>;
    }>>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showSwapModal, setShowSwapModal] = useState(false);
    const [showCoverModal, setShowCoverModal] = useState(false);
    const [modalError, setModalError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [selectedShift, setSelectedShift] = useState<HrShift | null>(null);
    const [assignmentType, setAssignmentType] = useState<ShiftAssignmentType>('specific_date');
    const [assignDate, setAssignDate] = useState(startDate);
    const [assignEndDate, setAssignEndDate] = useState(endDate);
    const [dayOfWeek, setDayOfWeek] = useState(1);

    const [targetEmployee, setTargetEmployee] = useState<EmployeeSummary | null>(null);
    const [swapDate, setSwapDate] = useState(startDate);
    const [targetSwapDate, setTargetSwapDate] = useState(startDate);
    const [swapReason, setSwapReason] = useState('');

    const [absentEmployee, setAbsentEmployee] = useState<EmployeeSummary | null>(null);
    const [coverEmployee, setCoverEmployee] = useState<EmployeeSummary | null>(null);
    const [coverDate, setCoverDate] = useState(startDate);
    const [coverReason, setCoverReason] = useState<ShiftReallocationReason>('sick_cover');

    const fetchRoster = useCallback(() => {
        setLoading(true);
        setError(null);
        getRoster({ start_date: startDate, end_date: endDate })
            .then((data) => setRosterData(data))
            .catch((err) => setError(extractHrApiError(err, 'Failed to load roster')))
            .finally(() => setLoading(false));
    }, [startDate, endDate]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchRoster();
        });
    }, [fetchRoster]);

    const handleAssign = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !selectedShift) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await assignShift({
                employee_id: selectedEmployee.id,
                shift_id: selectedShift.id,
                assignment_type: assignmentType,
                start_date: assignmentType === 'recurring' ? undefined : assignDate,
                end_date: assignmentType === 'date_range' ? assignEndDate : undefined,
                day_of_week: assignmentType === 'recurring' ? dayOfWeek : undefined,
            });
            setShowAssignModal(false);
            fetchRoster();
        } catch (err) {
            setModalError(extractHrApiError(err, 'Assignment failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const handleSwap = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !targetEmployee) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await requestShiftSwap({
                requester_employee_id: selectedEmployee.id,
                target_employee_id: targetEmployee.id,
                swap_date: swapDate,
                target_swap_date: targetSwapDate,
                reason: swapReason,
            });
            setShowSwapModal(false);
            setSwapReason('');
            fetchRoster();
        } catch (err) {
            setModalError(extractHrApiError(err, 'Shift swap request failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const handleCover = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!absentEmployee || !coverEmployee || !selectedShift) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await reallocateShiftCover({
                absent_employee_id: absentEmployee.id,
                cover_employee_id: coverEmployee.id,
                shift_id: selectedShift.id,
                reallocation_date: coverDate,
                reason_code: coverReason,
            });
            setShowCoverModal(false);
            fetchRoster();
        } catch (err) {
            setModalError(extractHrApiError(err, 'Reallocation failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const days: string[] = [];
    if (rosterData.length > 0 && rosterData[0].days) {
        days.push(...Object.keys(rosterData[0].days).sort());
    }

    return (
        <div className="space-y-4">
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            <div className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex items-center gap-2">
                        <label className="text-sm font-medium text-slate-700">From:</label>
                        <input
                            type="date"
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <label className="text-sm font-medium text-slate-700">To:</label>
                        <input
                            type="date"
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                    </div>
                    <Button variant="secondary" size="sm" onClick={fetchRoster}>
                        Refresh
                    </Button>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" size="sm" onClick={() => { setModalError(null); setShowSwapModal(true); }}>
                        Request Swap
                    </Button>
                    {canManage && (
                        <>
                            <Button variant="secondary" size="sm" onClick={() => { setModalError(null); setShowCoverModal(true); }}>
                                Reallocate Cover
                            </Button>
                            <Button variant="primary" size="sm" onClick={() => { setModalError(null); setShowAssignModal(true); }}>
                                + Assign Shift
                            </Button>
                        </>
                    )}
                </div>
            </div>

            {loading ? (
                <LoadingState label="Loading roster schedule..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                                    Employee
                                </th>
                                {days.map((day) => {
                                    const d = new Date(`${day}T00:00:00Z`);
                                    const dayName = d.toLocaleDateString('en-US', { weekday: 'short', timeZone: 'UTC' });
                                    const dayNum = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' });
                                    return (
                                        <th key={day} className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-700">
                                            <div>{dayName}</div>
                                            <div className="text-[10px] text-slate-400">{dayNum}</div>
                                        </th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {rosterData.length === 0 ? (
                                <tr>
                                    <td colSpan={days.length + 1} className="py-8 text-center text-sm text-slate-500">
                                        No active employees found for this period.
                                    </td>
                                </tr>
                            ) : (
                                rosterData.map((row) => (
                                    <tr key={row.employee.id} className="hover:bg-slate-50">
                                        <td className="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-3 font-medium text-slate-900">
                                            <div>{row.employee.display_name}</div>
                                            <div className="text-xs text-slate-400">{row.employee.employee_number}</div>
                                        </td>
                                        {days.map((day) => {
                                            const cell = row.days[day];
                                            if (!cell) {
                                                return <td key={day} className="px-2 py-3 text-center text-xs text-slate-400">-</td>;
                                            }
                                            if (cell.is_holiday) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <span className="inline-block rounded bg-purple-100 px-2 py-1 text-[11px] font-medium text-purple-800">
                                                            {cell.holiday_name || 'Holiday'}
                                                        </span>
                                                    </td>
                                                );
                                            }
                                            if (cell.is_rest_day) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <span className="inline-block rounded bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">
                                                            Off
                                                        </span>
                                                    </td>
                                                );
                                            }
                                            if (cell.shift) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <div
                                                            className="inline-block rounded px-2 py-1 text-[11px] font-semibold text-white shadow-2xs"
                                                            style={{ backgroundColor: cell.shift.color || '#3B82F6' }}
                                                            title={`${cell.shift.name} (${cell.shift.start_time.slice(0, 5)} - ${cell.shift.end_time.slice(0, 5)}) - Source: ${cell.source}`}
                                                        >
                                                            {cell.shift.code}
                                                        </div>
                                                        <div className="text-[9px] text-slate-400">{cell.shift.start_time.slice(0, 5)}</div>
                                                    </td>
                                                );
                                            }
                                            return <td key={day} className="px-2 py-3 text-center text-xs text-slate-400">-</td>;
                                        })}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            <Modal open={showAssignModal} title="Assign Shift to Employee" onClose={() => setShowAssignModal(false)}>
                <form onSubmit={handleAssign} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Employee" value={selectedEmployee} onChange={setSelectedEmployee} />
                    <HrShiftSelect label="Shift" value={selectedShift} onChange={setSelectedShift} />
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
                            <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={assignDate} onChange={(e) => setAssignDate(e.target.value)} required />
                        </div>
                    )}
                    {assignmentType === 'date_range' && (
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-sm font-medium text-slate-700">Start Date</label>
                                <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={assignDate} onChange={(e) => setAssignDate(e.target.value)} required />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-slate-700">End Date</label>
                                <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={assignEndDate} onChange={(e) => setAssignEndDate(e.target.value)} required />
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
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowAssignModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={submitting} disabled={!selectedEmployee || !selectedShift}>Save Assignment</Button>
                    </div>
                </form>
            </Modal>

            <Modal open={showSwapModal} title="Request Shift Swap" onClose={() => setShowSwapModal(false)}>
                <form onSubmit={handleSwap} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Requester Employee" value={selectedEmployee} onChange={setSelectedEmployee} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Requester Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={swapDate} onChange={(e) => setSwapDate(e.target.value)} required />
                    </div>
                    <EmployeeLookupSelect label="Target Peer Employee" value={targetEmployee} onChange={setTargetEmployee} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Target Peer Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={targetSwapDate} onChange={(e) => setTargetSwapDate(e.target.value)} required />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Reason</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={swapReason} onChange={(e) => setSwapReason(e.target.value)} required />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowSwapModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={submitting} disabled={!selectedEmployee || !targetEmployee || !swapReason}>Submit Swap</Button>
                    </div>
                </form>
            </Modal>

            <Modal open={showCoverModal} title="Assign Absent Staff Cover" onClose={() => setShowCoverModal(false)}>
                <form onSubmit={handleCover} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Absent Employee" value={absentEmployee} onChange={setAbsentEmployee} />
                    <EmployeeLookupSelect label="Covering Employee" value={coverEmployee} onChange={setCoverEmployee} />
                    <HrShiftSelect label="Cover Shift" value={selectedShift} onChange={setSelectedShift} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={coverDate} onChange={(e) => setCoverDate(e.target.value)} required />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Reason Code</label>
                        <select className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" value={coverReason} onChange={(e) => setCoverReason(e.target.value as ShiftReallocationReason)}>
                            {SHIFT_REALLOCATION_REASON_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowCoverModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={submitting} disabled={!absentEmployee || !coverEmployee || !selectedShift}>Save Reallocation</Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
