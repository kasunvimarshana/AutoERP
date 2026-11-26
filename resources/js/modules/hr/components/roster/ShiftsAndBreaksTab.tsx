/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginationMeta } from '@/shared/types/pagination';
import {
    createShift,
    createShiftBreak,
    deleteShift,
    deleteShiftBreak,
    listShiftBreaks,
    listShifts,
    updateShift,
    updateShiftBreak,
} from '../../hrApi';
import {
    DEFAULT_SHIFT_COLOR,
    SHIFT_BREAK_TYPE_OPTIONS,
    SHIFT_STATUS_OPTIONS,
} from '../../hrConstants';
import type { HrShift, HrShiftBreak, HrShiftPayload, ShiftStatus } from '../../hrTypes';
import { extractHrApiError } from './rosterError';

interface ShiftsAndBreaksTabProps {
    canManage: boolean;
}

const emptyShiftForm = (): HrShiftPayload => ({
    code: '',
    name: '',
    color: DEFAULT_SHIFT_COLOR,
    start_time: '09:00',
    end_time: '17:00',
    is_overnight: false,
    grace_in_minutes: 15,
    grace_out_minutes: 15,
    half_day_threshold_minutes: 240,
    full_day_threshold_minutes: 480,
    is_break_paid: false,
    auto_deduct_break: false,
    auto_deduct_break_minutes: 0,
    auto_deduct_threshold_minutes: 300,
    is_flexible: false,
    status: 'active',
    notes: '',
});

export function ShiftsAndBreaksTab({ canManage }: ShiftsAndBreaksTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();

    const [shifts, setShifts] = useState<HrShift[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Shift modal
    const [showShiftModal, setShowShiftModal] = useState(false);
    const [editingShift, setEditingShift] = useState<HrShift | null>(null);
    const [shiftForm, setShiftForm] = useState<HrShiftPayload>(emptyShiftForm);
    const [savingShift, setSavingShift] = useState(false);

    // Breaks modal / panel
    const [selectedShiftForBreaks, setSelectedShiftForBreaks] = useState<HrShift | null>(null);
    const [breaks, setBreaks] = useState<HrShiftBreak[]>([]);
    const [loadingBreaks, setLoadingBreaks] = useState(false);
    const [editingBreak, setEditingBreak] = useState<HrShiftBreak | null>(null);
    const [breakName, setBreakName] = useState('');
    const [breakType, setBreakType] = useState('meal');
    const [breakStartTime, setBreakStartTime] = useState('');
    const [breakEndTime, setBreakEndTime] = useState('');
    const [breakDuration, setBreakDuration] = useState(30);
    const [breakIsPaid, setBreakIsPaid] = useState(false);
    const [breakIsMandatory, setBreakIsMandatory] = useState(true);
    const [savingBreak, setSavingBreak] = useState(false);

    const fetchShifts = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listShifts({ page: targetPage, per_page: 25 })
            .then((res) => {
                setShifts(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load shifts')))
            .finally(() => setLoading(false));
    }, [page]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchShifts(page);
        });
    }, [fetchShifts, page]);

    const openCreateShift = () => {
        setEditingShift(null);
        setShiftForm(emptyShiftForm());
        setModalError(null);
        setShowShiftModal(true);
    };

    const openEditShift = (shift: HrShift) => {
        setEditingShift(shift);
        setShiftForm({
            code: shift.code,
            name: shift.name,
            color: shift.color || DEFAULT_SHIFT_COLOR,
            start_time: shift.start_time.slice(0, 5),
            end_time: shift.end_time.slice(0, 5),
            is_overnight: shift.is_overnight,
            grace_in_minutes: shift.grace_in_minutes,
            grace_out_minutes: shift.grace_out_minutes,
            half_day_threshold_minutes: shift.half_day_threshold_minutes,
            full_day_threshold_minutes: shift.full_day_threshold_minutes,
            is_break_paid: shift.is_break_paid,
            auto_deduct_break: shift.auto_deduct_break,
            auto_deduct_break_minutes: shift.auto_deduct_break_minutes,
            auto_deduct_threshold_minutes: shift.auto_deduct_threshold_minutes,
            is_flexible: shift.is_flexible,
            status: shift.status,
            notes: shift.notes ?? '',
        });
        setModalError(null);
        setShowShiftModal(true);
    };

    const handleSaveShift = async (e: React.FormEvent) => {
        e.preventDefault();
        setSavingShift(true);
        setModalError(null);
        try {
            if (editingShift) {
                await updateShift(editingShift.id, {
                    ...shiftForm,
                    notes: shiftForm.notes || null,
                    expected_version: editingShift.row_version,
                });
            } else {
                await createShift({
                    ...shiftForm,
                    notes: shiftForm.notes || null,
                });
            }
            setShowShiftModal(false);
            fetchShifts(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save shift'));
        } finally {
            setSavingShift(false);
        }
    };

    const handleDeleteShift = async (shift: HrShift) => {
        const ok = await confirm({
            title: 'Delete Shift',
            message: `Delete shift "${shift.code} - ${shift.name}"? Shifts referenced by assignments, swaps, rotations, or attendance cannot be deleted.`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteShift(shift.id, shift.row_version);
            fetchShifts(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete shift'));
        }
    };

    const loadBreaksForShift = useCallback((shift: HrShift) => {
        setLoadingBreaks(true);
        listShiftBreaks(shift.id)
            .then((data) => setBreaks(data))
            .catch((err) => setModalError(extractHrApiError(err, 'Failed to load shift breaks')))
            .finally(() => setLoadingBreaks(false));
    }, []);

    const openBreaksModal = (shift: HrShift) => {
        setSelectedShiftForBreaks(shift);
        setModalError(null);
        resetBreakForm();
        loadBreaksForShift(shift);
    };

    const resetBreakForm = () => {
        setEditingBreak(null);
        setBreakName('');
        setBreakType('meal');
        setBreakStartTime('');
        setBreakEndTime('');
        setBreakDuration(30);
        setBreakIsPaid(false);
        setBreakIsMandatory(true);
    };

    const startEditBreak = (brk: HrShiftBreak) => {
        setEditingBreak(brk);
        setBreakName(brk.name);
        setBreakType(brk.break_type);
        setBreakStartTime(brk.start_time ? brk.start_time.slice(0, 5) : '');
        setBreakEndTime(brk.end_time ? brk.end_time.slice(0, 5) : '');
        setBreakDuration(brk.duration_minutes);
        setBreakIsPaid(brk.is_paid);
        setBreakIsMandatory(brk.is_mandatory);
    };

    const handleSaveBreak = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedShiftForBreaks) return;
        setSavingBreak(true);
        setModalError(null);
        try {
            const payload = {
                name: breakName,
                break_type: breakType,
                start_time: breakStartTime || null,
                end_time: breakEndTime || null,
                duration_minutes: breakDuration,
                is_paid: breakIsPaid,
                is_mandatory: breakIsMandatory,
            };
            if (editingBreak) {
                await updateShiftBreak(selectedShiftForBreaks.id, editingBreak.id, {
                    ...payload,
                    expected_version: editingBreak.row_version,
                });
            } else {
                await createShiftBreak(selectedShiftForBreaks.id, payload);
            }
            resetBreakForm();
            loadBreaksForShift(selectedShiftForBreaks);
            fetchShifts(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save shift break'));
        } finally {
            setSavingBreak(false);
        }
    };

    const handleDeleteBreak = async (brk: HrShiftBreak) => {
        if (!selectedShiftForBreaks) return;
        const ok = await confirm({
            title: 'Delete Shift Break',
            message: `Delete break "${brk.name}" (${brk.duration_minutes} mins)?`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setModalError(null);
        try {
            await deleteShiftBreak(selectedShiftForBreaks.id, brk.id, brk.row_version);
            if (editingBreak?.id === brk.id) resetBreakForm();
            loadBreaksForShift(selectedShiftForBreaks);
            fetchShifts(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to delete shift break'));
        }
    };

    return (
        <div className="space-y-4">
            {confirmDialog}
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                <div>
                    <h3 className="text-base font-semibold text-slate-900">Shift Definitions &amp; Breaks</h3>
                    <p className="text-xs text-slate-500">Configure shift timings, grace periods, thresholds, and scheduled breaks.</p>
                </div>
                {canManage && (
                    <Button variant="primary" size="sm" onClick={openCreateShift}>
                        + New Shift
                    </Button>
                )}
            </div>

            {loading ? (
                <LoadingState label="Loading shifts..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Shift</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Timing</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Grace (In/Out)</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Thresholds</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Breaks</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {shifts.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="py-8 text-center text-slate-500">No shifts configured yet.</td>
                                </tr>
                            ) : (
                                shifts.map((shift) => (
                                    <tr key={shift.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className="inline-block h-3 w-3 shrink-0 rounded-full"
                                                    style={{ backgroundColor: shift.color || DEFAULT_SHIFT_COLOR }}
                                                />
                                                <div>
                                                    <div className="font-semibold text-slate-900">{shift.name}</div>
                                                    <div className="text-xs text-slate-500">{shift.code}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            <div>{shift.start_time.slice(0, 5)} - {shift.end_time.slice(0, 5)}</div>
                                            <div className="text-xs text-slate-400">
                                                {shift.is_overnight ? 'Overnight' : 'Same day'}{shift.is_flexible ? ' • Flexible' : ''}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {shift.grace_in_minutes}m / {shift.grace_out_minutes}m
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            Half: {shift.half_day_threshold_minutes}m / Full: {shift.full_day_threshold_minutes}m
                                        </td>
                                        <td className="px-4 py-3">
                                            <button
                                                type="button"
                                                className="text-xs font-medium text-blue-600 hover:underline"
                                                onClick={() => openBreaksModal(shift)}
                                            >
                                                {shift.breaks ? `${shift.breaks.length} break(s)` : 'Manage Breaks'}
                                            </button>
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={shift.status} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="secondary" size="sm" onClick={() => openBreaksModal(shift)}>
                                                    Breaks
                                                </Button>
                                                {canManage && (
                                                    <>
                                                        <Button variant="secondary" size="sm" onClick={() => openEditShift(shift)}>
                                                            Edit
                                                        </Button>
                                                        <Button variant="danger" size="sm" onClick={() => handleDeleteShift(shift)}>
                                                            Delete
                                                        </Button>
                                                    </>
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

            <Pagination meta={meta} onPageChange={setPage} disabled={loading} />

            {/* Create / Edit Shift Modal */}
            <Modal open={showShiftModal} title={editingShift ? `Edit Shift: ${editingShift.code}` : 'Create Shift'} onClose={() => setShowShiftModal(false)}>
                <form onSubmit={handleSaveShift} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Code</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                value={shiftForm.code}
                                onChange={(e) => setShiftForm((f) => ({ ...f, code: e.target.value }))}
                                maxLength={32}
                                required
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="text-sm font-medium text-slate-700">Name</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                value={shiftForm.name}
                                onChange={(e) => setShiftForm((f) => ({ ...f, name: e.target.value }))}
                                maxLength={100}
                                required
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Start Time</label>
                            <input
                                type="time"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                value={shiftForm.start_time}
                                onChange={(e) => setShiftForm((f) => ({ ...f, start_time: e.target.value }))}
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">End Time</label>
                            <input
                                type="time"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                value={shiftForm.end_time}
                                onChange={(e) => setShiftForm((f) => ({ ...f, end_time: e.target.value }))}
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">Color</label>
                            <input
                                type="color"
                                className="mt-1 h-9 w-full rounded-md border border-slate-300 p-1"
                                value={shiftForm.color}
                                onChange={(e) => setShiftForm((f) => ({ ...f, color: e.target.value }))}
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">Status</label>
                            <select
                                className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                                value={shiftForm.status}
                                onChange={(e) => setShiftForm((f) => ({ ...f, status: e.target.value as ShiftStatus }))}
                            >
                                {SHIFT_STATUS_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label className="text-xs font-medium text-slate-700">Grace In (min)</label>
                            <input
                                type="number"
                                min={0}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                value={shiftForm.grace_in_minutes}
                                onChange={(e) => setShiftForm((f) => ({ ...f, grace_in_minutes: Number(e.target.value) }))}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-700">Grace Out (min)</label>
                            <input
                                type="number"
                                min={0}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                value={shiftForm.grace_out_minutes}
                                onChange={(e) => setShiftForm((f) => ({ ...f, grace_out_minutes: Number(e.target.value) }))}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-700">Half Day (min)</label>
                            <input
                                type="number"
                                min={0}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                value={shiftForm.half_day_threshold_minutes}
                                onChange={(e) => setShiftForm((f) => ({ ...f, half_day_threshold_minutes: Number(e.target.value) }))}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-700">Full Day (min)</label>
                            <input
                                type="number"
                                min={0}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                value={shiftForm.full_day_threshold_minutes}
                                onChange={(e) => setShiftForm((f) => ({ ...f, full_day_threshold_minutes: Number(e.target.value) }))}
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-4 text-sm text-slate-700">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={shiftForm.is_overnight}
                                onChange={(e) => setShiftForm((f) => ({ ...f, is_overnight: e.target.checked }))}
                            />
                            Overnight shift
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={shiftForm.is_flexible}
                                onChange={(e) => setShiftForm((f) => ({ ...f, is_flexible: e.target.checked }))}
                            />
                            Flexible timing
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={shiftForm.is_break_paid}
                                onChange={(e) => setShiftForm((f) => ({ ...f, is_break_paid: e.target.checked }))}
                            />
                            Breaks paid
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={shiftForm.auto_deduct_break}
                                onChange={(e) => setShiftForm((f) => ({ ...f, auto_deduct_break: e.target.checked }))}
                            />
                            Auto-deduct break
                        </label>
                    </div>

                    {shiftForm.auto_deduct_break && (
                        <div className="grid grid-cols-2 gap-3 rounded-md bg-slate-50 p-3">
                            <div>
                                <label className="text-xs font-medium text-slate-700">Auto-Deduct Minutes</label>
                                <input
                                    type="number"
                                    min={1}
                                    className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                    value={shiftForm.auto_deduct_break_minutes}
                                    onChange={(e) => setShiftForm((f) => ({ ...f, auto_deduct_break_minutes: Number(e.target.value) }))}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-xs font-medium text-slate-700">After Worked Threshold (min)</label>
                                <input
                                    type="number"
                                    min={0}
                                    className="mt-1 w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                    value={shiftForm.auto_deduct_threshold_minutes}
                                    onChange={(e) => setShiftForm((f) => ({ ...f, auto_deduct_threshold_minutes: Number(e.target.value) }))}
                                />
                            </div>
                        </div>
                    )}

                    <div>
                        <label className="text-sm font-medium text-slate-700">Notes</label>
                        <textarea
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            rows={2}
                            value={shiftForm.notes ?? ''}
                            onChange={(e) => setShiftForm((f) => ({ ...f, notes: e.target.value }))}
                        />
                    </div>

                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowShiftModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={savingShift}>
                            {editingShift ? 'Update Shift' : 'Create Shift'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Shift Breaks Modal */}
            <Modal
                open={selectedShiftForBreaks !== null}
                title={selectedShiftForBreaks ? `Breaks for ${selectedShiftForBreaks.code} - ${selectedShiftForBreaks.name}` : 'Shift Breaks'}
                onClose={() => setSelectedShiftForBreaks(null)}
            >
                <div className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    {loadingBreaks ? (
                        <LoadingState label="Loading shift breaks..." />
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Name</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Type</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Window</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Duration</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Flags</th>
                                        {canManage && <th className="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-700">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {breaks.length === 0 ? (
                                        <tr>
                                            <td colSpan={canManage ? 6 : 5} className="py-4 text-center text-slate-500">No breaks configured for this shift.</td>
                                        </tr>
                                    ) : (
                                        breaks.map((brk) => (
                                            <tr key={brk.id}>
                                                <td className="px-3 py-2 font-medium text-slate-900">{brk.name}</td>
                                                <td className="px-3 py-2 capitalize text-slate-600">{brk.break_type}</td>
                                                <td className="px-3 py-2 text-slate-600">
                                                    {brk.start_time && brk.end_time ? `${brk.start_time.slice(0, 5)} - ${brk.end_time.slice(0, 5)}` : 'Anytime'}
                                                </td>
                                                <td className="px-3 py-2 text-slate-700">{brk.duration_minutes} min</td>
                                                <td className="px-3 py-2 text-xs text-slate-600">
                                                    {brk.is_paid ? 'Paid' : 'Unpaid'} • {brk.is_mandatory ? 'Mandatory' : 'Optional'}
                                                </td>
                                                {canManage && (
                                                    <td className="px-3 py-2 text-right">
                                                        <div className="flex justify-end gap-1.5">
                                                            <Button variant="secondary" size="sm" onClick={() => startEditBreak(brk)}>Edit</Button>
                                                            <Button variant="danger" size="sm" onClick={() => handleDeleteBreak(brk)}>Delete</Button>
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

                    {canManage && (
                        <form onSubmit={handleSaveBreak} className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h4 className="text-sm font-semibold text-slate-800">
                                {editingBreak ? `Edit Break: ${editingBreak.name}` : 'Add New Break'}
                            </h4>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label className="text-xs font-medium text-slate-700">Break Name</label>
                                    <input
                                        type="text"
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={breakName}
                                        onChange={(e) => setBreakName(e.target.value)}
                                        maxLength={50}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-slate-700">Break Type</label>
                                    <select
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={breakType}
                                        onChange={(e) => setBreakType(e.target.value)}
                                    >
                                        {SHIFT_BREAK_TYPE_OPTIONS.map((opt) => (
                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-slate-700">Duration (minutes)</label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={480}
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={breakDuration}
                                        onChange={(e) => setBreakDuration(Number(e.target.value))}
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div>
                                    <label className="text-xs font-medium text-slate-700">Start Time (opt)</label>
                                    <input
                                        type="time"
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={breakStartTime}
                                        onChange={(e) => setBreakStartTime(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-slate-700">End Time (opt)</label>
                                    <input
                                        type="time"
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={breakEndTime}
                                        onChange={(e) => setBreakEndTime(e.target.value)}
                                    />
                                </div>
                                <label className="flex items-center gap-2 pt-5 text-sm text-slate-700">
                                    <input type="checkbox" checked={breakIsPaid} onChange={(e) => setBreakIsPaid(e.target.checked)} />
                                    Paid Break
                                </label>
                                <label className="flex items-center gap-2 pt-5 text-sm text-slate-700">
                                    <input type="checkbox" checked={breakIsMandatory} onChange={(e) => setBreakIsMandatory(e.target.checked)} />
                                    Mandatory
                                </label>
                            </div>
                            <div className="flex justify-end gap-2">
                                {editingBreak && (
                                    <Button type="button" variant="secondary" size="sm" onClick={resetBreakForm}>
                                        Cancel Edit
                                    </Button>
                                )}
                                <Button type="submit" variant="primary" size="sm" loading={savingBreak}>
                                    {editingBreak ? 'Update Break' : 'Add Break'}
                                </Button>
                            </div>
                        </form>
                    )}
                </div>
            </Modal>
        </div>
    );
}
