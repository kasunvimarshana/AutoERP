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
import { businessDateInputValue, shiftBusinessDate } from '@/shared/utils/businessDate';
import { HrShiftSelect } from './components/HrShiftSelect';
import { EmployeeLookupSelect } from './components/EmployeeLookupSelect';
import { assignShift, getRoster, reallocateShiftCover, requestShiftSwap } from './hrApi';
import { hrPermissions } from './hrPermissions';
import type { EmployeeSummary, HrShift } from './hrTypes';

function currentBusinessWeekDate(offsetFromSunday: number): string {
    const today = businessDateInputValue();
    const [year, month, day] = today.split('-').map(Number);
    const dayOfWeek = new Date(Date.UTC(year, month - 1, day)).getUTCDay();
    return shiftBusinessDate(today, offsetFromSunday - dayOfWeek);
}

export default function RosterSchedulerPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.shiftsView);
    const canManage = hasPermission(auth, hrPermissions.shiftsManage);

    const [startDate, setStartDate] = useState(() => currentBusinessWeekDate(1)); // Monday
    const [endDate, setEndDate] = useState(() => currentBusinessWeekDate(7)); // Sunday

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

    // Modal States
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showSwapModal, setShowSwapModal] = useState(false);
    const [showCoverModal, setShowCoverModal] = useState(false);

    // Form inputs
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [selectedShift, setSelectedShift] = useState<HrShift | null>(null);
    const [assignmentType, setAssignmentType] = useState<'recurring' | 'date_range' | 'specific_date'>('specific_date');
    const [assignDate, setAssignDate] = useState(startDate);
    const [assignEndDate, setAssignEndDate] = useState(endDate);
    const [dayOfWeek, setDayOfWeek] = useState(1);

    // Swap Form
    const [targetEmployee, setTargetEmployee] = useState<EmployeeSummary | null>(null);
    const [swapDate, setSwapDate] = useState(startDate);
    const [targetSwapDate, setTargetSwapDate] = useState(startDate);
    const [swapReason, setSwapReason] = useState('');

    // Cover Form
    const [absentEmployee, setAbsentEmployee] = useState<EmployeeSummary | null>(null);
    const [coverEmployee, setCoverEmployee] = useState<EmployeeSummary | null>(null);
    const [coverDate, setCoverDate] = useState(startDate);
    const [coverReason, setCoverReason] = useState<'sick_cover' | 'emergency_cover' | 'surge_demand' | 'operational_need'>('sick_cover');

    const fetchRoster = useCallback(() => {
        setLoading(true);
        setError(null);
        getRoster({ start_date: startDate, end_date: endDate })
            .then((data) => setRosterData(data))
            .catch((err) => setError(err?.response?.data?.message || 'Failed to load roster'))
            .finally(() => setLoading(false));
    }, [startDate, endDate]);

    useEffect(() => {
        if (canView) {
            queueMicrotask(() => {
                fetchRoster();
            });
        }
    }, [canView, fetchRoster]);

    if (!canView) return <AccessDeniedPage />;

    const handleAssign = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !selectedShift) return;
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
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Assignment failed');
        }
    };

    const handleSwap = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !targetEmployee) return;
        try {
            await requestShiftSwap({
                requester_employee_id: selectedEmployee.id,
                target_employee_id: targetEmployee.id,
                swap_date: swapDate,
                target_swap_date: targetSwapDate,
                reason: swapReason,
            });
            setShowSwapModal(false);
            fetchRoster();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Shift swap request failed');
        }
    };

    const handleCover = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!absentEmployee || !coverEmployee || !selectedShift) return;
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
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Reallocation failed');
        }
    };

    // Calculate dates array for columns
    const days: string[] = [];
    if (rosterData.length > 0 && rosterData[0].days) {
        days.push(...Object.keys(rosterData[0].days).sort());
    }

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Shift Roster & Scheduling"
                description="Manage employee shift schedules, dynamic overrides, peer swaps, and absent coverage."
                actions={
                    canManage && (
                        <div className="flex gap-2">
                            <Button variant="secondary" onClick={() => setShowSwapModal(true)}>
                                Request Swap
                            </Button>
                            <Button variant="secondary" onClick={() => setShowCoverModal(true)}>
                                Reallocate Cover
                            </Button>
                            <Button variant="primary" onClick={() => setShowAssignModal(true)}>
                                + Assign Shift
                            </Button>
                        </div>
                    )
                }
            />

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            {/* Filter controls */}
            <div className="flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div className="flex items-center gap-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">From:</label>
                    <input
                        type="date"
                        className="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                        value={startDate}
                        onChange={(e) => setStartDate(e.target.value)}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">To:</label>
                    <input
                        type="date"
                        className="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                        value={endDate}
                        onChange={(e) => setEndDate(e.target.value)}
                    />
                </div>
                <Button variant="secondary" onClick={fetchRoster}>
                    Refresh
                </Button>
            </div>

            {loading ? (
                <LoadingState label="Loading roster schedule..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th className="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    Employee
                                </th>
                                {days.map((day) => {
                                    const d = new Date(day);
                                    const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
                                    const dayNum = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                    return (
                                        <th key={day} className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            <div>{dayName}</div>
                                            <div className="text-[10px] text-gray-400">{dayNum}</div>
                                        </th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {rosterData.length === 0 ? (
                                <tr>
                                    <td colSpan={days.length + 1} className="py-8 text-center text-sm text-gray-500">
                                        No active employees found for this period.
                                    </td>
                                </tr>
                            ) : (
                                rosterData.map((row) => (
                                    <tr key={row.employee.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td className="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-3 font-medium text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                            <div>{row.employee.display_name}</div>
                                            <div className="text-xs text-gray-400">{row.employee.employee_number}</div>
                                        </td>
                                        {days.map((day) => {
                                            const cell = row.days[day];
                                            if (!cell) {
                                                return <td key={day} className="px-2 py-3 text-center text-xs text-gray-400">-</td>;
                                            }

                                            if (cell.is_holiday) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <span className="inline-block rounded bg-purple-100 px-2 py-1 text-[11px] font-medium text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                            {cell.holiday_name || 'Holiday'}
                                                        </span>
                                                    </td>
                                                );
                                            }

                                            if (cell.is_rest_day) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <span className="inline-block rounded bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                            Off
                                                        </span>
                                                    </td>
                                                );
                                            }

                                            if (cell.shift) {
                                                return (
                                                    <td key={day} className="px-2 py-2 text-center">
                                                        <div
                                                            className="inline-block cursor-pointer rounded px-2 py-1 text-[11px] font-semibold text-white shadow-sm"
                                                            style={{ backgroundColor: cell.shift.color || '#3B82F6' }}
                                                            title={`${cell.shift.name} (${cell.shift.start_time.slice(0, 5)} - ${cell.shift.end_time.slice(0, 5)}) - Source: ${cell.source}`}
                                                        >
                                                            {cell.shift.code}
                                                        </div>
                                                        <div className="text-[9px] text-gray-400">{cell.shift.start_time.slice(0, 5)}</div>
                                                    </td>
                                                );
                                            }

                                            return <td key={day} className="px-2 py-3 text-center text-xs text-gray-400">-</td>;
                                        })}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Modal: Assign Shift */}
            {showAssignModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Shift to Employee</h3>
                        <form onSubmit={handleAssign} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Employee"
                                value={selectedEmployee}
                                onChange={setSelectedEmployee}
                            />
                            <HrShiftSelect
                                label="Shift"
                                value={selectedShift}
                                onChange={setSelectedShift}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Assignment Type</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={assignmentType}
                                    onChange={(e) => setAssignmentType(e.target.value as typeof assignmentType)}
                                >
                                    <option value="specific_date">Specific Single Date</option>
                                    <option value="date_range">Date Range</option>
                                    <option value="recurring">Weekly Recurring Day</option>
                                </select>
                            </div>

                            {assignmentType === 'specific_date' && (
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={assignDate}
                                        onChange={(e) => setAssignDate(e.target.value)}
                                        required
                                    />
                                </div>
                            )}

                            {assignmentType === 'date_range' && (
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                        <input
                                            type="date"
                                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                            value={assignDate}
                                            onChange={(e) => setAssignDate(e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                        <input
                                            type="date"
                                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                            value={assignEndDate}
                                            onChange={(e) => setAssignEndDate(e.target.value)}
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            {assignmentType === 'recurring' && (
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Day of Week</label>
                                    <select
                                        className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={dayOfWeek}
                                        onChange={(e) => setDayOfWeek(Number(e.target.value))}
                                    >
                                        <option value={1}>Monday</option>
                                        <option value={2}>Tuesday</option>
                                        <option value={3}>Wednesday</option>
                                        <option value={4}>Thursday</option>
                                        <option value={5}>Friday</option>
                                        <option value={6}>Saturday</option>
                                        <option value={7}>Sunday</option>
                                    </select>
                                </div>
                            )}

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowAssignModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!selectedEmployee || !selectedShift}>
                                    Save Assignment
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Shift Swap */}
            {showSwapModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Request Shift Swap</h3>
                        <form onSubmit={handleSwap} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Requester Employee"
                                value={selectedEmployee}
                                onChange={setSelectedEmployee}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Requester Date</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={swapDate}
                                    onChange={(e) => setSwapDate(e.target.value)}
                                    required
                                />
                            </div>
                            <EmployeeLookupSelect
                                label="Target Peer Employee"
                                value={targetEmployee}
                                onChange={setTargetEmployee}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Target Peer Date</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={targetSwapDate}
                                    onChange={(e) => setTargetSwapDate(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    rows={2}
                                    value={swapReason}
                                    onChange={(e) => setSwapReason(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowSwapModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!selectedEmployee || !targetEmployee || !swapReason}>
                                    Submit Swap
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Reallocate Cover */}
            {showCoverModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Absent Staff Cover</h3>
                        <form onSubmit={handleCover} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Absent Employee"
                                value={absentEmployee}
                                onChange={setAbsentEmployee}
                            />
                            <EmployeeLookupSelect
                                label="Covering Employee"
                                value={coverEmployee}
                                onChange={setCoverEmployee}
                            />
                            <HrShiftSelect
                                label="Cover Shift"
                                value={selectedShift}
                                onChange={setSelectedShift}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={coverDate}
                                    onChange={(e) => setCoverDate(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Reason Code</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={coverReason}
                                    onChange={(e) => setCoverReason(e.target.value as typeof coverReason)}
                                >
                                    <option value="sick_cover">Sick Leave Cover</option>
                                    <option value="emergency_cover">Emergency Leave Cover</option>
                                    <option value="surge_demand">Surge Demand</option>
                                    <option value="operational_need">Operational Need</option>
                                </select>
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowCoverModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!absentEmployee || !coverEmployee || !selectedShift}>
                                    Save Reallocation
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
