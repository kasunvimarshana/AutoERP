/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { EmployeeLookupSelect } from './components/EmployeeLookupSelect';
import {
    actionAttendanceRegularization,
    directAdjustAttendanceRecord,
    listAttendanceRecords,
    listAttendanceRegularizations,
    punchAttendance,
    requestAttendanceRegularization,
} from './hrApi';
import { hrPermissions } from './hrPermissions';
import type { EmployeeSummary, HrAttendanceRecord, HrAttendanceRegularization } from './hrTypes';

export default function AttendanceManagementPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.attendanceView);
    const canManage = hasPermission(auth, hrPermissions.attendanceManage);
    const canPunch = hasPermission(auth, hrPermissions.attendancePunch);

    const [activeTab, setActiveTab] = useState<'records' | 'regularizations'>('records');

    // Filter states
    const [startDate, setStartDate] = useState(() => {
        const d = new Date();
        d.setDate(d.getDate() - 7);
        return d.toISOString().slice(0, 10);
    });
    const [endDate, setEndDate] = useState(() => new Date().toISOString().slice(0, 10));
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);

    // Records data
    const [records, setRecords] = useState<HrAttendanceRecord[]>([]);
    const [regularizations, setRegularizations] = useState<HrAttendanceRegularization[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Modals
    const [showRegularizeModal, setShowRegularizeModal] = useState(false);
    const [showAdjustModal, setShowAdjustModal] = useState(false);
    const [selectedRecordForAdjust, setSelectedRecordForAdjust] = useState<HrAttendanceRecord | null>(null);

    // Regularize form
    const [regDate, setRegDate] = useState(() => new Date().toISOString().slice(0, 10));
    const [regType, setRegType] = useState<'missing_in' | 'missing_out' | 'both_missing' | 'incorrect_time' | 'forgot_to_punch' | 'on_duty'>('forgot_to_punch');
    const [regInTime, setRegInTime] = useState('');
    const [regOutTime, setRegOutTime] = useState('');
    const [regReason, setRegReason] = useState('forgot_to_punch');
    const [regNotes, setRegNotes] = useState('');

    // Direct adjust form
    const [adjustStatus, setAdjustStatus] = useState<string>('present');
    const [adjustWorkedMinutes, setAdjustWorkedMinutes] = useState<number>(480);
    const [adjustOtMinutes, setAdjustOtMinutes] = useState<number>(0);
    const [adjustReason, setAdjustReason] = useState<string>('');

    const fetchRecords = () => {
        setLoading(true);
        setError(null);
        listAttendanceRecords({
            start_date: startDate,
            end_date: endDate,
            employee_id: selectedEmployee?.id,
        })
            .then((res) => setRecords(res.data))
            .catch((err) => setError(err?.response?.data?.message || 'Failed to load attendance records'))
            .finally(() => setLoading(false));
    };

    const fetchRegularizations = () => {
        listAttendanceRegularizations({ status: 'pending' })
            .then((data) => setRegularizations(data))
            .catch(() => {});
    };

    useEffect(() => {
        if (canView) {
            fetchRecords();
            fetchRegularizations();
        }
    }, [startDate, endDate, selectedEmployee]);

    if (!canView) return <AccessDeniedPage />;

    const handlePunch = async (type: string) => {
        if (!selectedEmployee) {
            setError('Please select an employee to record punch.');
            return;
        }
        try {
            await punchAttendance({
                employee_id: selectedEmployee.id,
                punch_type: type,
            });
            fetchRecords();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Punch failed');
        }
    };

    const handleRegularizeSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee) return;
        try {
            await requestAttendanceRegularization({
                employee_id: selectedEmployee.id,
                attendance_date: regDate,
                regularization_type: regType,
                reason_code: regReason,
                employee_notes: regNotes,
                proposed_clock_in: regInTime ? `${regDate} ${regInTime}:00` : undefined,
                proposed_clock_out: regOutTime ? `${regDate} ${regOutTime}:00` : undefined,
            });
            setShowRegularizeModal(false);
            fetchRecords();
            fetchRegularizations();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Regularization request failed');
        }
    };

    const handleActionRegularization = async (id: number, approve: boolean) => {
        try {
            await actionAttendanceRegularization(id, approve);
            fetchRegularizations();
            fetchRecords();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to action regularization');
        }
    };

    const handleDirectAdjustSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRecordForAdjust) return;
        try {
            await directAdjustAttendanceRecord(selectedRecordForAdjust.id, {
                new_status: adjustStatus,
                new_worked_minutes: Number(adjustWorkedMinutes),
                new_overtime_minutes: Number(adjustOtMinutes),
                adjustment_reason: adjustReason,
            });
            setShowAdjustModal(false);
            fetchRecords();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Direct adjustment failed');
        }
    };

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Attendance & Time Tracking"
                description="Monitor clock-ins, daily work hours, multi-breaks, overtime, and punch regularizations."
                actions={
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => setShowRegularizeModal(true)}>
                            Request Regularization
                        </Button>
                    </div>
                }
            />

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            {/* Quick Web Punch Widget */}
            {canPunch && (
                <div className="rounded-lg border border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 shadow-sm dark:border-gray-700 dark:from-gray-800 dark:to-gray-850">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex flex-1 items-center gap-4">
                            <div className="w-64">
                                <EmployeeLookupSelect
                                    label="Select Active Employee"
                                    value={selectedEmployee}
                                    onChange={setSelectedEmployee}
                                />
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wider text-gray-500">Today's Date</span>
                                <div className="text-sm font-semibold text-gray-800 dark:text-gray-200">{new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' })}</div>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button variant="primary" onClick={() => handlePunch('in')}>
                                Clock In
                            </Button>
                            <Button variant="secondary" onClick={() => handlePunch('break_out')}>
                                Start Break
                            </Button>
                            <Button variant="secondary" onClick={() => handlePunch('break_in')}>
                                End Break
                            </Button>
                            <Button variant="danger" onClick={() => handlePunch('out')}>
                                Clock Out
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Navigation Tabs */}
            <div className="flex border-b border-gray-200 dark:border-gray-700">
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'records' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('records')}
                >
                    Daily Attendance Records
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'regularizations' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('regularizations')}
                >
                    Regularization Requests ({regularizations.length})
                </button>
            </div>

            {activeTab === 'records' && (
                <div className="space-y-4">
                    {/* Filters */}
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
                        <Button variant="secondary" onClick={fetchRecords}>
                            Apply Filter
                        </Button>
                    </div>

                    {loading ? (
                        <LoadingState label="Loading attendance records..." />
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Date</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Employee</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Shift</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">First In / Last Out</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Worked</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Overtime</th>
                                        <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Status</th>
                                        {canManage && <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {records.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="py-8 text-center text-sm text-gray-500">
                                                No attendance records found.
                                            </td>
                                        </tr>
                                    ) : (
                                        records.map((r) => {
                                            const workedHours = (r.total_worked_minutes / 60).toFixed(1);
                                            const otHours = (r.overtime_minutes / 60).toFixed(1);
                                            return (
                                                <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {r.attendance_date}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                        <div className="font-medium text-gray-900 dark:text-gray-100">{r.employee?.display_name}</div>
                                                        <div className="text-xs text-gray-400">{r.employee?.employee_number}</div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                        {r.shift ? (
                                                            <span className="inline-block rounded px-2 py-0.5 text-xs font-medium text-white" style={{ backgroundColor: r.shift.color || '#3B82F6' }}>
                                                                {r.shift.code}
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                        <div>In: {r.first_punch_in ? r.first_punch_in.slice(11, 16) : '-'}</div>
                                                        <div className="text-xs text-gray-400">Out: {r.last_punch_out ? r.last_punch_out.slice(11, 16) : '-'}</div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-gray-200">
                                                        {workedHours} hrs
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                                        {r.overtime_minutes > 0 ? (
                                                            <span className="font-semibold text-green-600 dark:text-green-400">+{otHours} hrs</span>
                                                        ) : (
                                                            '-'
                                                        )}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-center">
                                                        <StatusBadge status={r.status} />
                                                        {r.is_regularized && (
                                                            <span className="ml-1 text-[10px] text-amber-500 font-semibold" title="Regularized">
                                                                [REG]
                                                            </span>
                                                        )}
                                                    </td>
                                                    {canManage && (
                                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                            <button
                                                                className="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                                                onClick={() => {
                                                                    setSelectedRecordForAdjust(r);
                                                                    setAdjustStatus(r.status);
                                                                    setAdjustWorkedMinutes(r.total_worked_minutes);
                                                                    setAdjustOtMinutes(r.overtime_minutes);
                                                                    setShowAdjustModal(true);
                                                                }}
                                                            >
                                                                Adjust
                                                            </button>
                                                        </td>
                                                    )}
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'regularizations' && (
                <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Date</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Employee</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Type & Reason</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Proposed Times</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Employee Notes</th>
                                {canManage && <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Action</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {regularizations.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-8 text-center text-sm text-gray-500">
                                        No pending regularizations.
                                    </td>
                                </tr>
                            ) : (
                                regularizations.map((reg) => (
                                    <tr key={reg.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {reg.attendance_date}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {reg.employee?.display_name}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            <div className="font-semibold text-gray-800 dark:text-gray-200">{reg.regularization_type}</div>
                                            <div className="text-xs text-gray-400">{reg.reason_code}</div>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            <div>In: {reg.proposed_clock_in ? reg.proposed_clock_in.slice(11, 16) : '-'}</div>
                                            <div>Out: {reg.proposed_clock_out ? reg.proposed_clock_out.slice(11, 16) : '-'}</div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {reg.employee_notes}
                                        </td>
                                        {canManage && (
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                <div className="flex justify-center gap-2">
                                                    <Button size="sm" variant="primary" onClick={() => handleActionRegularization(reg.id, true)}>
                                                        Approve
                                                    </Button>
                                                    <Button size="sm" variant="danger" onClick={() => handleActionRegularization(reg.id, false)}>
                                                        Reject
                                                    </Button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Modal: Request Regularization */}
            {showRegularizeModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Submit Attendance Regularization</h3>
                        <form onSubmit={handleRegularizeSubmit} className="space-y-4">
                            <EmployeeLookupSelect
                                label="Employee"
                                value={selectedEmployee}
                                onChange={setSelectedEmployee}
                            />
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={regDate}
                                    onChange={(e) => setRegDate(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Regularization Type</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={regType}
                                    onChange={(e) => setRegType(e.target.value as typeof regType)}
                                >
                                    <option value="missing_in">Missing Clock In</option>
                                    <option value="missing_out">Missing Clock Out</option>
                                    <option value="both_missing">Both Punches Missing</option>
                                    <option value="incorrect_time">Incorrect Punch Time</option>
                                    <option value="forgot_to_punch">Forgot to Punch</option>
                                    <option value="on_duty">On Duty / Client Visit</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Proposed In Time</label>
                                    <input
                                        type="time"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={regInTime}
                                        onChange={(e) => setRegInTime(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Proposed Out Time</label>
                                    <input
                                        type="time"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={regOutTime}
                                        onChange={(e) => setRegOutTime(e.target.value)}
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={regReason}
                                    onChange={(e) => setRegReason(e.target.value)}
                                >
                                    <option value="forgot_badge">Forgot ID Badge</option>
                                    <option value="system_outage">System / Internet Outage</option>
                                    <option value="client_visit">Field Work / Client Visit</option>
                                    <option value="biometric_error">Biometric Reader Error</option>
                                    <option value="emergency">Personal Emergency</option>
                                    <option value="forgot_to_punch">Forgot to Punch</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Employee Notes</label>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    rows={2}
                                    value={regNotes}
                                    onChange={(e) => setRegNotes(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowRegularizeModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!selectedEmployee || !regNotes}>
                                    Submit Request
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Direct Adjust Attendance */}
            {showAdjustModal && selectedRecordForAdjust && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Direct Manager Adjustment</h3>
                        <form onSubmit={handleDirectAdjustSubmit} className="space-y-4">
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    value={adjustStatus}
                                    onChange={(e) => setAdjustStatus(e.target.value)}
                                >
                                    <option value="present">Present</option>
                                    <option value="late">Late</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="absent">Absent</option>
                                    <option value="on_leave">On Leave</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Worked Minutes</label>
                                    <input
                                        type="number"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={adjustWorkedMinutes}
                                        onChange={(e) => setAdjustWorkedMinutes(Number(e.target.value))}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Overtime Minutes</label>
                                    <input
                                        type="number"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        value={adjustOtMinutes}
                                        onChange={(e) => setAdjustOtMinutes(Number(e.target.value))}
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Mandatory Audit Reason</label>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    rows={2}
                                    value={adjustReason}
                                    onChange={(e) => setAdjustReason(e.target.value)}
                                    placeholder="Explain why this record is being adjusted directly..."
                                    required
                                />
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <Button variant="secondary" onClick={() => setShowAdjustModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" type="submit" disabled={!adjustReason}>
                                    Save Adjustment
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
