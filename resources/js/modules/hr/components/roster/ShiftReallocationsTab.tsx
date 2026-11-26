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
import type { PaginationMeta } from '@/shared/types/pagination';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    cancelShiftReallocation,
    listShiftReallocations,
    reallocateShiftCover,
    updateShiftReallocation,
} from '../../hrApi';
import {
    getShiftReallocationReasonLabel,
    SHIFT_REALLOCATION_REASON_OPTIONS,
} from '../../hrConstants';
import type { EmployeeSummary, HrShift, HrShiftReallocation, ShiftReallocationReason } from '../../hrTypes';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { HrShiftSelect } from '../HrShiftSelect';
import { extractHrApiError } from './rosterError';

interface ShiftReallocationsTabProps {
    canManage: boolean;
}

export function ShiftReallocationsTab({ canManage }: ShiftReallocationsTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();

    const [reallocations, setReallocations] = useState<HrShiftReallocation[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Filters
    const [filterDate, setFilterDate] = useState('');
    const [filterAbsent, setFilterAbsent] = useState<EmployeeSummary | null>(null);
    const [filterCover, setFilterCover] = useState<EmployeeSummary | null>(null);

    // Create / Edit Modal
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<HrShiftReallocation | null>(null);
    const [absentEmployee, setAbsentEmployee] = useState<EmployeeSummary | null>(null);
    const [coverEmployee, setCoverEmployee] = useState<EmployeeSummary | null>(null);
    const [shift, setShift] = useState<HrShift | null>(null);
    const [reallocationDate, setReallocationDate] = useState(() => businessDateInputValue());
    const [reasonCode, setReasonCode] = useState<ShiftReallocationReason>('sick_cover');
    const [notes, setNotes] = useState('');
    const [saving, setSaving] = useState(false);

    const fetchReallocations = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listShiftReallocations({
            reallocation_date: filterDate || undefined,
            absent_employee_id: filterAbsent?.id,
            cover_employee_id: filterCover?.id,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setReallocations(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load shift reallocations')))
            .finally(() => setLoading(false));
    }, [filterAbsent?.id, filterCover?.id, filterDate, page]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchReallocations(page);
        });
    }, [fetchReallocations, page]);

    const openCreate = () => {
        setEditing(null);
        setAbsentEmployee(null);
        setCoverEmployee(null);
        setShift(null);
        setReallocationDate(businessDateInputValue());
        setReasonCode('sick_cover');
        setNotes('');
        setModalError(null);
        setShowModal(true);
    };

    const openEdit = (item: HrShiftReallocation) => {
        setEditing(item);
        setAbsentEmployee(item.absent_employee ?? null);
        setCoverEmployee(item.cover_employee ?? null);
        setShift(item.shift ?? null);
        setReallocationDate(item.reallocation_date.slice(0, 10));
        setReasonCode(item.reason_code);
        setNotes(item.notes ?? '');
        setModalError(null);
        setShowModal(true);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!absentEmployee || !coverEmployee || !shift) return;
        setSaving(true);
        setModalError(null);
        try {
            const payload = {
                absent_employee_id: absentEmployee.id,
                cover_employee_id: coverEmployee.id,
                shift_id: shift.id,
                reallocation_date: reallocationDate,
                reason_code: reasonCode,
                notes: notes.trim() || null,
            };
            if (editing) {
                await updateShiftReallocation(editing.id, {
                    ...payload,
                    expected_version: editing.row_version,
                });
            } else {
                await reallocateShiftCover(payload);
            }
            setShowModal(false);
            fetchReallocations(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save shift reallocation'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (item: HrShiftReallocation) => {
        const ok = await confirm({
            title: 'Cancel Shift Reallocation',
            message: `Remove cover reallocation on ${item.reallocation_date.slice(0, 10)} (${item.cover_employee?.display_name ?? 'cover'} covering ${item.absent_employee?.display_name ?? 'absent'})?`,
            confirmLabel: 'Remove',
        });
        if (!ok) return;
        setError(null);
        try {
            await cancelShiftReallocation(item.id, item.row_version);
            fetchReallocations(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to remove shift reallocation'));
        }
    };

    return (
        <div className="space-y-4">
            {confirmDialog}
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700">Filter by Date</label>
                        <input
                            type="date"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            value={filterDate}
                            onChange={(e) => {
                                setFilterDate(e.target.value);
                                setPage(1);
                            }}
                        />
                    </div>
                    <EmployeeLookupSelect
                        label="Absent Employee"
                        value={filterAbsent}
                        onChange={(val) => {
                            setFilterAbsent(val);
                            setPage(1);
                        }}
                    />
                    <EmployeeLookupSelect
                        label="Cover Employee"
                        value={filterCover}
                        onChange={(val) => {
                            setFilterCover(val);
                            setPage(1);
                        }}
                    />
                </div>
                {canManage && (
                    <Button variant="primary" size="sm" onClick={openCreate}>
                        + Reallocate Cover
                    </Button>
                )}
            </div>

            {loading ? (
                <LoadingState label="Loading shift reallocations..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Date</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Absent Employee</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Covering Employee</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Shift</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Reason</th>
                                {canManage && <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {reallocations.length === 0 ? (
                                <tr>
                                    <td colSpan={canManage ? 6 : 5} className="py-8 text-center text-slate-500">No shift reallocations found.</td>
                                </tr>
                            ) : (
                                reallocations.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3 font-medium text-slate-900">{item.reallocation_date.slice(0, 10)}</td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{item.absent_employee?.display_name ?? '-'}</div>
                                            <div className="text-xs text-slate-400">{item.absent_employee?.employee_number ?? ''}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{item.cover_employee?.display_name ?? '-'}</div>
                                            <div className="text-xs text-slate-400">{item.cover_employee?.employee_number ?? ''}</div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {item.shift ? `${item.shift.code} - ${item.shift.name}` : '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="text-slate-700">{getShiftReallocationReasonLabel(item.reason_code)}</div>
                                            {item.notes && <div className="text-xs text-slate-400">{item.notes}</div>}
                                        </td>
                                        {canManage && (
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="secondary" size="sm" onClick={() => openEdit(item)}>Edit</Button>
                                                    <Button variant="danger" size="sm" onClick={() => handleDelete(item)}>Remove</Button>
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

            <Pagination meta={meta} onPageChange={setPage} disabled={loading} />

            <Modal open={showModal} title={editing ? 'Edit Shift Reallocation' : 'Assign Absent Staff Cover'} onClose={() => setShowModal(false)}>
                <form onSubmit={handleSave} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Absent Employee" value={absentEmployee} onChange={setAbsentEmployee} />
                    <EmployeeLookupSelect label="Covering Employee" value={coverEmployee} onChange={setCoverEmployee} />
                    <HrShiftSelect label="Cover Shift" value={shift} onChange={setShift} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Reallocation Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={reallocationDate} onChange={(e) => setReallocationDate(e.target.value)} required />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Reason Code</label>
                        <select className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" value={reasonCode} onChange={(e) => setReasonCode(e.target.value as ShiftReallocationReason)}>
                            {SHIFT_REALLOCATION_REASON_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Notes</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={notes} onChange={(e) => setNotes(e.target.value)} />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={saving} disabled={!absentEmployee || !coverEmployee || !shift}>
                            {editing ? 'Update Reallocation' : 'Save Reallocation'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
