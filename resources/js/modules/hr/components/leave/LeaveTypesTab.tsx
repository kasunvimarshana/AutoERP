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
import { extractHrApiError } from '../roster/rosterError';
import {
    createLeaveType,
    deleteLeaveType,
    listLeaveTypes,
    updateLeaveType,
} from '../../hrApi';
import { DEFAULT_LEAVE_TYPE_COLOR, LEAVE_TYPE_STATUS_OPTIONS } from '../../hrConstants';
import type { HrLeaveType, HrLeaveTypePayload, LeaveTypeStatus } from '../../hrTypes';

interface LeaveTypesTabProps {
    canManage: boolean;
}

const emptyLeaveTypeForm = (): HrLeaveTypePayload => ({
    code: '',
    name: '',
    color: DEFAULT_LEAVE_TYPE_COLOR,
    is_paid: true,
    requires_attachment: false,
    attachment_mandatory_after_days: 2,
    allow_half_day: true,
    allow_hourly: false,
    is_compensatory: false,
    sandwich_rule_applied: false,
    status: 'active',
});

export function LeaveTypesTab({ canManage }: LeaveTypesTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();

    const [leaveTypes, setLeaveTypes] = useState<HrLeaveType[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [statusFilter, setStatusFilter] = useState<'' | LeaveTypeStatus>('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [showModal, setShowModal] = useState(false);
    const [editingType, setEditingType] = useState<HrLeaveType | null>(null);
    const [form, setForm] = useState<HrLeaveTypePayload>(emptyLeaveTypeForm);
    const [modalError, setModalError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const fetchLeaveTypes = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listLeaveTypes({
            status: statusFilter || undefined,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setLeaveTypes(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load leave types')))
            .finally(() => setLoading(false));
    }, [page, statusFilter]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchLeaveTypes(page);
        });
    }, [fetchLeaveTypes, page]);

    const openCreate = () => {
        setEditingType(null);
        setForm(emptyLeaveTypeForm());
        setModalError(null);
        setShowModal(true);
    };

    const openEdit = (lt: HrLeaveType) => {
        setEditingType(lt);
        setForm({
            code: lt.code,
            name: lt.name,
            color: lt.color || DEFAULT_LEAVE_TYPE_COLOR,
            is_paid: lt.is_paid,
            requires_attachment: lt.requires_attachment,
            attachment_mandatory_after_days: lt.attachment_mandatory_after_days,
            allow_half_day: lt.allow_half_day,
            allow_hourly: lt.allow_hourly,
            is_compensatory: lt.is_compensatory,
            sandwich_rule_applied: lt.sandwich_rule_applied,
            status: lt.status,
        });
        setModalError(null);
        setShowModal(true);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setModalError(null);
        try {
            if (editingType) {
                await updateLeaveType(editingType.id, {
                    ...form,
                    expected_version: editingType.row_version,
                });
            } else {
                await createLeaveType(form);
            }
            setShowModal(false);
            fetchLeaveTypes(editingType ? page : 1);
            if (!editingType) setPage(1);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save leave type'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (lt: HrLeaveType) => {
        const ok = await confirm({
            title: 'Delete Leave Type',
            message: `Are you sure you want to delete leave type "${lt.name}" (${lt.code})?`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;

        setError(null);
        try {
            await deleteLeaveType(lt.id, lt.row_version);
            fetchLeaveTypes(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete leave type'));
        }
    };

    return (
        <div className="space-y-6">
            {confirmDialog}

            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select
                        className="mt-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                        value={statusFilter}
                        onChange={(e) => {
                            setStatusFilter(e.target.value as '' | LeaveTypeStatus);
                            setPage(1);
                        }}
                    >
                        <option value="">All Statuses</option>
                        {LEAVE_TYPE_STATUS_OPTIONS.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                </div>
                {canManage && (
                    <Button variant="primary" onClick={openCreate}>
                        + New Leave Type
                    </Button>
                )}
            </div>

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">Leave Types</h3>
                </div>
                {loading ? (
                    <LoadingState label="Loading leave types..." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Code</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Name</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Paid</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Half Day</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Comp-Off</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Sandwich</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Status</th>
                                    {canManage && (
                                        <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Actions</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {leaveTypes.length === 0 ? (
                                    <tr>
                                        <td colSpan={canManage ? 8 : 7} className="py-8 text-center text-sm text-gray-500">
                                            No leave types configured.
                                        </td>
                                    </tr>
                                ) : (
                                    leaveTypes.map((lt) => (
                                        <tr key={lt.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-gray-100">
                                                {lt.code}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                                                <span className="inline-flex items-center gap-2">
                                                    <span
                                                        className="inline-block h-3 w-3 rounded-full"
                                                        style={{ backgroundColor: lt.color || DEFAULT_LEAVE_TYPE_COLOR }}
                                                    />
                                                    {lt.name}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                                {lt.is_paid ? 'Yes' : 'No'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                                {lt.allow_half_day ? 'Yes' : 'No'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                                {lt.is_compensatory ? 'Yes' : 'No'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                                {lt.sandwich_rule_applied ? 'Yes' : 'No'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center">
                                                <StatusBadge status={lt.status} />
                                            </td>
                                            {canManage && (
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                    <div className="flex justify-center gap-2">
                                                        <Button size="sm" variant="secondary" onClick={() => openEdit(lt)}>
                                                            Edit
                                                        </Button>
                                                        <Button size="sm" variant="danger" onClick={() => handleDelete(lt)}>
                                                            Delete
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
                {meta && <Pagination meta={meta} onPageChange={setPage} />}
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editingType ? `Edit Leave Type: ${editingType.name}` : 'New Leave Type'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    {modalError && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                            {modalError}
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                            <input
                                type="text"
                                maxLength={32}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={form.code}
                                onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={form.status ?? 'active'}
                                onChange={(e) => setForm({ ...form, status: e.target.value as LeaveTypeStatus })}
                            >
                                {LEAVE_TYPE_STATUS_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input
                                type="text"
                                maxLength={100}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={form.name}
                                onChange={(e) => setForm({ ...form, name: e.target.value })}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Color</label>
                            <input
                                type="color"
                                className="mt-1 h-9 w-full rounded-md border border-gray-300 p-1 dark:border-gray-700 dark:bg-gray-900"
                                value={form.color ?? DEFAULT_LEAVE_TYPE_COLOR}
                                onChange={(e) => setForm({ ...form, color: e.target.value })}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.is_paid)}
                                onChange={(e) => setForm({ ...form, is_paid: e.target.checked })}
                            />
                            Paid Leave
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.allow_half_day)}
                                onChange={(e) => setForm({ ...form, allow_half_day: e.target.checked })}
                            />
                            Allow Half Day
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.allow_hourly)}
                                onChange={(e) => setForm({ ...form, allow_hourly: e.target.checked })}
                            />
                            Allow Hourly
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.is_compensatory)}
                                onChange={(e) => setForm({ ...form, is_compensatory: e.target.checked })}
                            />
                            Compensatory Leave
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.sandwich_rule_applied)}
                                onChange={(e) => setForm({ ...form, sandwich_rule_applied: e.target.checked })}
                            />
                            Apply Sandwich Rule
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(form.requires_attachment)}
                                onChange={(e) => setForm({ ...form, requires_attachment: e.target.checked })}
                            />
                            Requires Attachment
                        </label>
                    </div>
                    {form.requires_attachment && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Attachment Mandatory After (Days)
                            </label>
                            <input
                                type="number"
                                min={1}
                                className="mt-1 w-32 rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={form.attachment_mandatory_after_days ?? 2}
                                onChange={(e) => setForm({ ...form, attachment_mandatory_after_days: Number(e.target.value) || 1 })}
                            />
                        </div>
                    )}
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" onClick={() => setShowModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={saving || !form.code.trim() || !form.name.trim()}>
                            {saving ? 'Saving...' : editingType ? 'Save Changes' : 'Create Leave Type'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
