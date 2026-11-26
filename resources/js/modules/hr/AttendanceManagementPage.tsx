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
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { GeofencesAndKiosksTab } from './components/attendance/GeofencesAndKiosksTab';
import { EmployeeLookupSelect } from './components/EmployeeLookupSelect';
import {
    actionAttendanceRegularization,
    cancelAttendanceRegularization,
    commitAttendanceImport,
    directAdjustAttendanceRecord,
    getKioskQrToken,
    listAttendanceAnomalies,
    listAttendanceKiosks,
    listAttendanceRecords,
    listAttendanceRegularizations,
    lockAttendancePeriod,
    previewAttendanceImport,
    punchAttendance,
    quickResolveAttendanceAnomaly,
    requestAttendanceRegularization,
    scanAttendanceAnomalies,
} from './hrApi';
import { hrPermissions } from './hrPermissions';
import type {
    BatchImportPreview,
    EmployeeSummary,
    HrAttendanceKiosk,
    HrAttendanceRecord,
    HrAttendanceRegularization,
} from './hrTypes';

type ActiveTab = 'records' | 'regularizations' | 'anomalies' | 'kiosk' | 'geofences' | 'import';

export default function AttendanceManagementPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.attendanceView);
    const canManage = hasPermission(auth, hrPermissions.attendanceManage);
    const canPunch = hasPermission(auth, hrPermissions.attendancePunch);
    const canRegularize = hasPermission(auth, hrPermissions.attendanceRegularize);
    const { confirm, confirmDialog } = useConfirmDialog();

    const [activeTab, setActiveTab] = useState<ActiveTab>('records');

    // Filter states
    const [startDate, setStartDate] = useState(() => businessDateInputValue(new Date(), undefined, -7));
    const [endDate, setEndDate] = useState(() => businessDateInputValue());
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);

    // Records data
    const [records, setRecords] = useState<HrAttendanceRecord[]>([]);
    const [regularizations, setRegularizations] = useState<HrAttendanceRegularization[]>([]);
    const [anomalies, setAnomalies] = useState<HrAttendanceRecord[]>([]);
    const [kiosks, setKiosks] = useState<HrAttendanceKiosk[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<ApiError | null>(null);
    const [lockSuccessMessage, setLockSuccessMessage] = useState<string | null>(null);

    // Modals
    const [showRegularizeModal, setShowRegularizeModal] = useState(false);
    const [showAdjustModal, setShowAdjustModal] = useState(false);
    const [showQuickResolveModal, setShowQuickResolveModal] = useState(false);
    const [showLockModal, setShowLockModal] = useState(false);
    const [lockStartDate, setLockStartDate] = useState(() => businessDateInputValue(new Date(), undefined, -7));
    const [lockEndDate, setLockEndDate] = useState(() => businessDateInputValue());
    const [lockSubmitting, setLockSubmitting] = useState(false);
    const [selectedRecordForAdjust, setSelectedRecordForAdjust] = useState<HrAttendanceRecord | null>(null);
    const [selectedRecordForQuickResolve, setSelectedRecordForQuickResolve] = useState<HrAttendanceRecord | null>(null);

    // Regularize form
    const [regDate, setRegDate] = useState(() => businessDateInputValue());
    const [regType, setRegType] = useState<'missing_in' | 'missing_out' | 'both_missing' | 'incorrect_time' | 'forgot_to_punch' | 'on_duty'>('forgot_to_punch');
    const [regInTime, setRegInTime] = useState('');
    const [regOutTime, setRegOutTime] = useState('');
    const [regReason, setRegReason] = useState('forgot_to_punch');
    const [regNotes, setRegNotes] = useState('');

    // Quick resolve form
    const [quickInTime, setQuickInTime] = useState('');
    const [quickOutTime, setQuickOutTime] = useState('');
    const [quickReason, setQuickReason] = useState('');

    // Direct adjust form
    const [adjustStatus, setAdjustStatus] = useState<string>('present');
    const [adjustWorkedMinutes, setAdjustWorkedMinutes] = useState<number>(480);
    const [adjustOtMinutes, setAdjustOtMinutes] = useState<number>(0);
    const [adjustReason, setAdjustReason] = useState<string>('');

    // Dynamic QR Kiosk Terminal state
    const [selectedKioskId, setSelectedKioskId] = useState<number | null>(null);
    const [kioskToken, setKioskToken] = useState<string | null>(null);
    const [kioskExpiresIn, setKioskExpiresIn] = useState<number>(0);

    // Batch File Import state
    const [importFile, setImportFile] = useState<File | null>(null);
    const [importCsvContent, setImportCsvContent] = useState<string>('');
    const [importPreview, setImportPreview] = useState<BatchImportPreview | null>(null);
    const [importLoading, setImportLoading] = useState(false);
    const [importSuccessMessage, setImportSuccessMessage] = useState<string | null>(null);

    const fetchRecords = useCallback(() => {
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
    }, [startDate, endDate, selectedEmployee?.id]);

    const fetchRegularizations = useCallback(() => {
        listAttendanceRegularizations({ status: 'pending' })
            .then((res) => setRegularizations(res.data))
            .catch(() => {});
    }, []);

    const fetchAnomalies = useCallback(() => {
        listAttendanceAnomalies()
            .then((res) => setAnomalies(res.data))
            .catch(() => {});
    }, []);

    const fetchKiosks = useCallback(() => {
        listAttendanceKiosks({ per_page: 100 })
            .then((res) => {
                const data = res.data;
                setKiosks(data);
                if (data.length > 0) {
                    setSelectedKioskId((prev) => (prev === null ? data[0].id : prev));
                }
            })
            .catch(() => {});
    }, []);

    useEffect(() => {
        if (canView) {
            queueMicrotask(() => {
                fetchRecords();
                fetchRegularizations();
                fetchAnomalies();
                fetchKiosks();
            });
        }
    }, [canView, fetchRecords, fetchRegularizations, fetchAnomalies, fetchKiosks]);

    // Dynamic Kiosk Timer
    useEffect(() => {
        if (activeTab !== 'kiosk' || !selectedKioskId || !canManage) return;

        const refreshQr = () => {
            getKioskQrToken(selectedKioskId)
                .then((data) => {
                    setKioskToken(data.token);
                    setKioskExpiresIn(data.expires_in_seconds);
                })
                .catch(() => {});
        };

        refreshQr();
        const interval = setInterval(refreshQr, 10000);

        return () => clearInterval(interval);
    }, [activeTab, selectedKioskId, canManage]);

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
            fetchAnomalies();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Punch failed');
        }
    };

    const handleRegularizeSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee) return;
        setModalError(null);
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
            fetchAnomalies();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        }
    };

    const handleActionRegularization = async (reg: HrAttendanceRegularization, approve: boolean) => {
        setError(null);
        try {
            await actionAttendanceRegularization(reg.id, approve, undefined, reg.row_version);
            fetchRegularizations();
            fetchRecords();
            fetchAnomalies();
        } catch (err: unknown) {
            setError(toApiError(err).message);
        }
    };

    const handleCancelRegularization = async (reg: HrAttendanceRegularization) => {
        const confirmed = await confirm({
            title: 'Cancel Regularization',
            message: `Withdraw pending regularization request for ${reg.attendance_date}?`,
            confirmLabel: 'Withdraw Request',
            danger: true,
        });
        if (!confirmed) return;
        setError(null);
        try {
            await cancelAttendanceRegularization(reg.id, reg.row_version);
            fetchRegularizations();
            fetchRecords();
        } catch (err: unknown) {
            setError(toApiError(err).message);
        }
    };

    const handleDirectAdjustSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRecordForAdjust) return;
        setModalError(null);
        try {
            await directAdjustAttendanceRecord(selectedRecordForAdjust.id, {
                new_status: adjustStatus,
                new_worked_minutes: Number(adjustWorkedMinutes),
                new_overtime_minutes: Number(adjustOtMinutes),
                adjustment_reason: adjustReason,
                expected_version: selectedRecordForAdjust.row_version,
            });
            setShowAdjustModal(false);
            fetchRecords();
            fetchAnomalies();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        }
    };

    const handleQuickResolveSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRecordForQuickResolve) return;
        setModalError(null);
        try {
            await quickResolveAttendanceAnomaly(selectedRecordForQuickResolve.id, {
                in_time: quickInTime || undefined,
                out_time: quickOutTime || undefined,
                resolution_reason: quickReason,
            });
            setShowQuickResolveModal(false);
            setSelectedRecordForQuickResolve(null);
            fetchRecords();
            fetchAnomalies();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        }
    };

    const handleLockPeriodSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManage || lockSubmitting) return;
        setLockSubmitting(true);
        setModalError(null);
        setLockSuccessMessage(null);
        try {
            const res = await lockAttendancePeriod({
                start_date: lockStartDate,
                end_date: lockEndDate,
            });
            setShowLockModal(false);
            setLockSuccessMessage(`Finalized ${res.locked_count} attendance record(s) from ${lockStartDate} to ${lockEndDate}.`);
            fetchRecords();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        } finally {
            setLockSubmitting(false);
        }
    };

    const handleScanAnomalies = async () => {
        try {
            setLoading(true);
            await scanAttendanceAnomalies({ date: startDate });
            fetchAnomalies();
            fetchRecords();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Anomaly scan failed');
        } finally {
            setLoading(false);
        }
    };

    const handlePreviewImport = async () => {
        setImportLoading(true);
        setError(null);
        setImportSuccessMessage(null);
        try {
            const preview = await previewAttendanceImport({
                file: importFile ?? undefined,
                csv_content: importCsvContent || undefined,
            });
            setImportPreview(preview);
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to parse import preview');
        } finally {
            setImportLoading(false);
        }
    };

    const handleCommitImport = async () => {
        if (!importPreview || importPreview.valid_rows === 0) return;
        setImportLoading(true);
        setError(null);
        try {
            await commitAttendanceImport(
                importFile
                    ? { file: importFile }
                    : {
                        file_name: 'manual_paste.csv',
                        csv_content: importCsvContent,
                    },
            );
            setImportSuccessMessage(`Successfully imported ${importPreview.valid_rows} attendance punches.`);
            setImportPreview(null);
            setImportFile(null);
            setImportCsvContent('');
            fetchRecords();
            fetchAnomalies();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Commit failed');
        } finally {
            setImportLoading(false);
        }
    };

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Attendance & Time Tracking"
                description="Monitor clock-ins, daily work hours, biometric hardware, NFC/RFID cards, rotating QR kiosks, and anomalies."
                actions={
                    <div className="flex gap-2">
                        {canManage && (
                            <>
                                <Button variant="secondary" onClick={handleScanAnomalies}>
                                    Scan Missing Punches
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        setModalError(null);
                                        setLockStartDate(startDate);
                                        setLockEndDate(endDate);
                                        setShowLockModal(true);
                                    }}
                                >
                                    Lock Period
                                </Button>
                            </>
                        )}
                        {canRegularize && (
                            <Button
                                variant="primary"
                                onClick={() => {
                                    setModalError(null);
                                    setShowRegularizeModal(true);
                                }}
                            >
                                Request Regularization
                            </Button>
                        )}
                    </div>
                }
            />

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            {lockSuccessMessage && (
                <div className="rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                    {lockSuccessMessage}
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
                                <div className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' })}
                                </div>
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
                    Daily Records
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'anomalies' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('anomalies')}
                >
                    Missing Punches & Anomalies ({anomalies.length})
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'regularizations' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('regularizations')}
                >
                    Regularization Requests ({regularizations.length})
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'kiosk' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('kiosk')}
                >
                    Dynamic QR Kiosk
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'geofences' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('geofences')}
                >
                    Geofences & Kiosks
                </button>
                <button
                    className={`border-b-2 px-4 py-2 text-sm font-medium ${activeTab === 'import' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                    onClick={() => setActiveTab('import')}
                >
                    Batch File Import
                </button>
            </div>

            {/* TAB 1: Daily Attendance Records */}
            {activeTab === 'records' && (
                <div className="space-y-4">
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
                        <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Date</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Employee</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Shift</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">In / Out</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Worked</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Overtime</th>
                                        <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Status</th>
                                        <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    {records.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="py-6 text-center text-gray-500">
                                                No attendance records found for this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        records.map((r) => (
                                            <tr key={r.id}>
                                                <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                                    {r.attendance_date}
                                                </td>
                                                <td className="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                    {r.employee?.display_name ?? 'Unknown employee'}
                                                </td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                    {r.shift?.name ?? 'Unassigned'}
                                                </td>
                                                <td className="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                                    {r.first_punch_in ? r.first_punch_in.slice(11, 16) : '—'} /{' '}
                                                    {r.last_punch_out ? r.last_punch_out.slice(11, 16) : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                    {(r.total_worked_minutes / 60).toFixed(1)} hrs
                                                </td>
                                                <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                    {r.overtime_minutes > 0 ? `${(r.overtime_minutes / 60).toFixed(1)} hrs` : '0'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-col gap-1 items-start">
                                                        <StatusBadge status={r.status} />
                                                        {r.requires_regularization && (
                                                            <span className="inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                                                {r.anomaly_type?.replace('_', ' ').toUpperCase() ?? 'NEEDS REG'}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right space-x-2">
                                                    {canManage && r.requires_regularization && (
                                                        <Button
                                                            variant="primary"
                                                            onClick={() => {
                                                                setModalError(null);
                                                                setSelectedRecordForQuickResolve(r);
                                                                setQuickInTime(r.first_punch_in ? r.first_punch_in.slice(0, 19) : `${r.attendance_date} 08:00:00`);
                                                                setQuickOutTime(r.last_punch_out ? r.last_punch_out.slice(0, 19) : `${r.attendance_date} 17:00:00`);
                                                                setQuickReason('Admin quick resolution of missing punch');
                                                                setShowQuickResolveModal(true);
                                                            }}
                                                        >
                                                            Quick Fix
                                                        </Button>
                                                    )}
                                                    {canManage && (
                                                        <Button
                                                            variant="secondary"
                                                            onClick={() => {
                                                                setModalError(null);
                                                                setSelectedRecordForAdjust(r);
                                                                setAdjustStatus(r.status);
                                                                setAdjustWorkedMinutes(r.total_worked_minutes);
                                                                setAdjustOtMinutes(r.overtime_minutes);
                                                                setShowAdjustModal(true);
                                                            }}
                                                        >
                                                            Adjust
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {/* TAB 2: Missing Punches & Anomaly Desk */}
            {activeTab === 'anomalies' && (
                <div className="space-y-4">
                    <div className="flex items-center justify-between rounded-lg bg-amber-50 p-4 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-800">
                        <div>
                            <h4 className="font-semibold text-amber-900 dark:text-amber-200">Anomaly Resolution Desk</h4>
                            <p className="text-sm text-amber-700 dark:text-amber-300">
                                Records flagged with missing IN/OUT punches, unpaired breaks, or excessive hours requiring administrative or employee regularization.
                            </p>
                        </div>
                        {canManage && (
                            <Button variant="secondary" onClick={handleScanAnomalies}>
                                Run Anomaly Scan
                            </Button>
                        )}
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Date</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Employee</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Shift</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Anomaly Type</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">First In / Last Out</th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                {anomalies.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-8 text-center text-gray-500">
                                            No unresolved attendance anomalies found. All shifts are cleanly closed!
                                        </td>
                                    </tr>
                                ) : (
                                    anomalies.map((a) => (
                                        <tr key={a.id}>
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                                {a.attendance_date}
                                            </td>
                                            <td className="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                {a.employee?.display_name ?? 'Unknown employee'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {a.shift?.name ?? 'Standard Shift'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                                                    {a.anomaly_type ? a.anomaly_type.replace('_', ' ').toUpperCase() : 'MISSING PUNCH'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                                {a.first_punch_in ? a.first_punch_in.slice(11, 16) : '(MISSING)'} /{' '}
                                                {a.last_punch_out ? a.last_punch_out.slice(11, 16) : '(MISSING)'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {canManage && (
                                                    <Button
                                                        variant="primary"
                                                        onClick={() => {
                                                            setModalError(null);
                                                            setSelectedRecordForQuickResolve(a);
                                                            setQuickInTime(a.first_punch_in ? a.first_punch_in.slice(0, 19) : `${a.attendance_date} 08:00:00`);
                                                            setQuickOutTime(a.last_punch_out ? a.last_punch_out.slice(0, 19) : `${a.attendance_date} 17:00:00`);
                                                            setQuickReason('Quick-resolved via Anomaly Desk');
                                                            setShowQuickResolveModal(true);
                                                        }}
                                                    >
                                                        Resolve Anomaly
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* TAB 3: Regularization Requests */}
            {activeTab === 'regularizations' && (
                <div className="space-y-4">
                    <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Date</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Employee</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Reason</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Proposed Times</th>
                                    <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Status</th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                {regularizations.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-6 text-center text-gray-500">
                                            No pending regularization requests.
                                        </td>
                                    </tr>
                                ) : (
                                    regularizations.map((reg) => (
                                        <tr key={reg.id}>
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                                {reg.attendance_date}
                                            </td>
                                            <td className="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                {reg.employee?.display_name ?? 'Unknown employee'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                <div className="font-medium">{reg.reason_code}</div>
                                                <div className="text-xs text-gray-500">{reg.employee_notes}</div>
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                                {reg.proposed_clock_in ? reg.proposed_clock_in.slice(11, 16) : '—'} /{' '}
                                                {reg.proposed_clock_out ? reg.proposed_clock_out.slice(11, 16) : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={reg.status} />
                                            </td>
                                            <td className="px-4 py-3 text-right space-x-2">
                                                {canManage && reg.status === 'pending' && (
                                                    <>
                                                        <Button variant="primary" onClick={() => handleActionRegularization(reg, true)}>
                                                            Approve
                                                        </Button>
                                                        <Button variant="danger" onClick={() => handleActionRegularization(reg, false)}>
                                                            Reject
                                                        </Button>
                                                    </>
                                                )}
                                                {(canRegularize || canManage) && reg.status === 'pending' && (
                                                    <Button variant="secondary" onClick={() => handleCancelRegularization(reg)}>
                                                        Withdraw
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* TAB 4: Dynamic QR Kiosk Terminal */}
            {activeTab === 'kiosk' && (
                <div className="mx-auto max-w-2xl space-y-6 text-center">
                    <div className="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-850">
                        <h3 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Stationary Kiosk Rotating QR Terminal
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Displays dynamic, cryptographic HMAC-SHA256 rotating tokens that change every 20 seconds. Prevents photo copying and buddy punching.
                        </p>

                        <div className="mt-6 flex justify-center gap-4">
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Select Active Kiosk Terminal:
                            </label>
                            <select
                                className="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={selectedKioskId ?? ''}
                                onChange={(e) => setSelectedKioskId(Number(e.target.value))}
                            >
                                {kiosks.map((k) => (
                                    <option key={k.id} value={k.id}>
                                        {k.name} ({k.kiosk_code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="mt-8 flex flex-col items-center justify-center">
                            <div className="flex h-64 w-64 items-center justify-center rounded-2xl border-4 border-indigo-600 bg-gray-50 p-4 shadow-inner dark:border-indigo-500 dark:bg-gray-900">
                                <div className="space-y-3 text-center">
                                    <div className="mx-auto h-24 w-24 rounded-lg bg-indigo-100 p-2 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                        <svg className="h-full w-full" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-5 5h2v3h-2v-3zm2-3h3v2h-3v-2zm3 3h3v3h-3v-3z" />
                                        </svg>
                                    </div>
                                    <span className="block font-mono text-[10px] break-all text-gray-600 dark:text-gray-400">
                                        {kioskToken ?? 'Generating cryptographic token...'}
                                    </span>
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                                <span className="h-2.5 w-2.5 animate-ping rounded-full bg-emerald-500" />
                                <span className="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                    Rotates in: {kioskExpiresIn}s
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* TAB 4b: Geofences & Kiosks */}
            {activeTab === 'geofences' && (
                <GeofencesAndKiosksTab canManage={canManage} onKiosksChanged={fetchKiosks} />
            )}

            {/* TAB 5: Batch File Import */}
            {activeTab === 'import' && (
                <div className="space-y-6">
                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Two-Phase Attendance Batch Ingestion (CSV / Excel)
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Upload or paste attendance CSV records. The dry-run engine validates employee numbers, date/time formatting, and duplicate checks before executing an atomic batch commit.
                        </p>

                        <div className="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Upload CSV or Excel (.xlsx) File
                                </label>
                                <input
                                    type="file"
                                    accept=".csv,.txt,.xlsx"
                                    className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-300"
                                    onChange={(e) => {
                                        if (e.target.files && e.target.files[0]) {
                                            setImportFile(e.target.files[0]);
                                        }
                                    }}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Or Paste CSV Content
                                </label>
                                <textarea
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border border-gray-300 font-mono text-xs p-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    placeholder="employee_code,date,time,punch_type,notes&#10;EMP-01,2026-11-25,08:00:00,in,Morning shift"
                                    value={importCsvContent}
                                    onChange={(e) => setImportCsvContent(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="mt-4 flex gap-2">
                            <Button
                                variant="primary"
                                onClick={handlePreviewImport}
                                disabled={importLoading || (!importFile && !importCsvContent)}
                            >
                                {importLoading ? 'Validating...' : 'Run Dry Run Validation'}
                            </Button>
                        </div>
                    </div>

                    {importSuccessMessage && (
                        <div className="rounded-md bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 border border-emerald-200">
                            {importSuccessMessage}
                        </div>
                    )}

                    {importPreview && (
                        <div className="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h4 className="font-semibold text-gray-900 dark:text-gray-100">Dry Run Validation Summary</h4>
                                    <p className="text-sm text-gray-500">
                                        Total: {importPreview.total_rows} | Valid:{' '}
                                        <span className="font-bold text-emerald-600">{importPreview.valid_rows}</span> | Invalid:{' '}
                                        <span className="font-bold text-rose-600">{importPreview.invalid_rows}</span>
                                    </p>
                                </div>
                                <Button
                                    variant="primary"
                                    onClick={handleCommitImport}
                                    disabled={importPreview.invalid_rows > 0 || importLoading}
                                >
                                    {importLoading ? 'Committing...' : 'Commit Valid Batch'}
                                </Button>
                            </div>

                            <div className="max-h-80 overflow-y-auto rounded border border-gray-200 dark:border-gray-700">
                                <table className="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-850">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">Row</th>
                                            <th className="px-3 py-2 font-medium">Emp Code</th>
                                            <th className="px-3 py-2 font-medium">Name</th>
                                            <th className="px-3 py-2 font-medium">Date & Time</th>
                                            <th className="px-3 py-2 font-medium">Type</th>
                                            <th className="px-3 py-2 font-medium">Status / Errors</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        {importPreview.rows.map((row) => (
                                            <tr key={row.row_number} className={row.is_valid ? '' : 'bg-rose-50/50 dark:bg-rose-950/20'}>
                                                <td className="px-3 py-2 font-mono">{row.row_number}</td>
                                                <td className="px-3 py-2 font-medium">{row.employee_code}</td>
                                                <td className="px-3 py-2">{row.employee_name ?? '—'}</td>
                                                <td className="px-3 py-2 font-mono">{row.date} {row.time}</td>
                                                <td className="px-3 py-2 uppercase">{row.punch_type}</td>
                                                <td className="px-3 py-2">
                                                    {row.is_valid ? (
                                                        <span className="text-emerald-600 font-semibold">VALID</span>
                                                    ) : (
                                                        <span className="text-rose-600 font-medium">{row.errors.join('; ')}</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Modal: Quick Resolve Missing Punch */}
            <Modal
                open={showQuickResolveModal && selectedRecordForQuickResolve !== null}
                title="Quick Resolve Missing Punch"
                onClose={() => setShowQuickResolveModal(false)}
            >
                {selectedRecordForQuickResolve && (
                    <form onSubmit={handleQuickResolveSubmit} className="space-y-4">
                        <ErrorAlert error={modalError} inline />
                        <p className="text-xs text-gray-500">
                            Employee: {selectedRecordForQuickResolve.employee?.display_name ?? 'Unknown employee'} | Date: {selectedRecordForQuickResolve.attendance_date}
                        </p>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                First Clock In Timestamp
                            </label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={quickInTime}
                                onChange={(e) => setQuickInTime(e.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Last Clock Out Timestamp
                            </label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={quickOutTime}
                                onChange={(e) => setQuickOutTime(e.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Resolution Reason (Audit Trail)
                            </label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={quickReason}
                                onChange={(e) => setQuickReason(e.target.value)}
                                placeholder="e.g. Card tap failure confirmed by shift lead"
                                required
                            />
                        </div>
                        <div className="mt-6 flex justify-end gap-2">
                            <Button variant="secondary" type="button" onClick={() => setShowQuickResolveModal(false)}>
                                Cancel
                            </Button>
                            <Button variant="primary" type="submit" disabled={!quickReason}>
                                Apply Quick Fix
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>

            {/* Modal: Request Regularization */}
            <Modal
                open={showRegularizeModal}
                title="Submit Attendance Regularization"
                onClose={() => setShowRegularizeModal(false)}
            >
                <form onSubmit={handleRegularizeSubmit} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <EmployeeLookupSelect
                        label="Employee"
                        value={selectedEmployee}
                        onChange={setSelectedEmployee}
                    />
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Attendance Date</label>
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
                            onChange={(e) => setRegType(e.target.value as HrAttendanceRegularization['regularization_type'])}
                        >
                            <option value="missing_in">Missing In Punch</option>
                            <option value="missing_out">Missing Out Punch</option>
                            <option value="both_missing">Both Punches Missing</option>
                            <option value="incorrect_time">Incorrect Time Clocked</option>
                            <option value="forgot_to_punch">Forgot to Punch</option>
                            <option value="on_duty">Official On-Duty / Client Visit</option>
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
                        <input
                            type="text"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={regReason}
                            onChange={(e) => setRegReason(e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Detailed Notes</label>
                        <textarea
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            rows={3}
                            value={regNotes}
                            onChange={(e) => setRegNotes(e.target.value)}
                            placeholder="Explain why punch regularization is needed..."
                            required
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setShowRegularizeModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={!selectedEmployee || !regNotes}>
                            Submit Request
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal: Direct Adjust Attendance */}
            <Modal
                open={showAdjustModal && selectedRecordForAdjust !== null}
                title="Direct Manager Adjustment"
                onClose={() => setShowAdjustModal(false)}
            >
                {selectedRecordForAdjust && (
                    <form onSubmit={handleDirectAdjustSubmit} className="space-y-4">
                        <ErrorAlert error={modalError} inline />
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
                            <Button variant="secondary" type="button" onClick={() => setShowAdjustModal(false)}>
                                Cancel
                            </Button>
                            <Button variant="primary" type="submit" disabled={!adjustReason}>
                                Save Adjustment
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>

            {/* Modal: Lock Attendance Period */}
            <Modal
                open={showLockModal}
                title="Lock Attendance Period"
                onClose={() => setShowLockModal(false)}
                closeDisabled={lockSubmitting}
            >
                <form onSubmit={handleLockPeriodSubmit} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md p-3">
                        Finalizing an attendance period locks all open records in the selected date range. Finalized records cannot be edited or regularized.
                    </p>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={lockStartDate}
                                onChange={(e) => setLockStartDate(e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={lockEndDate}
                                onChange={(e) => setLockEndDate(e.target.value)}
                                required
                            />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setShowLockModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="danger" type="submit" disabled={lockSubmitting}>
                            {lockSubmitting ? 'Finalizing...' : 'Finalize & Lock Period'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {confirmDialog}
        </div>
    );
}
